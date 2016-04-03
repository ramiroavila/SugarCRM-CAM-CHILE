<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase as CustomerCase;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;



class AtencionClientesCrmUpdateCommand extends ContainerAwareCommand
{

    protected $client = null;
    protected $sessionId = -1;

    protected function configure()
    {
        $this
            ->setName('crm:atencion-clientes-update')
            ->setDescription('OBTIENE LOS CASOS DE ATENCION CRM CAM');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("CONECTANDO A WEBSERVICE VIA SOAP");
      
        try {

          $normalizer = new GetSetMethodNormalizer();
          $encoder = new JsonEncoder();
          $serializer = new Serializer(array($normalizer), array($encoder));

          //retrieve WSDL
          $this->client = new \nusoap_client("http://crm.cam-la.com/service/v4/soap.php?wsdl", 'true');

          $this->executeLogin($input,$output);
          
          $this->updateDataBase($input, $output, $serializer);

        
        } catch (Exception $e) {
          $output->writeln("ERROR: ".$e->getMessage());
        }
        
        $output->writeln("EJECUCION FINALIZADA. Good Bye!");
    }
  
    private function executeLogin(InputInterface $input, OutputInterface $output) {
    
       $output->writeln("EJECUTANDO AUTENTICACION.");
      
       $login_parameters = array(
         'user_auth' => array(
              'user_name' => 'admin',
              'password' => md5('.Gf45793.'),
              'version' => '1'
         ),
         'application_name' => 'SoapAlarmasCRM',
         'name_value_list' => array(),
       );

       $login_result = $this->client->call('login', $login_parameters);
      
       if (isset($login_result['faultstring'])) throw new \Exception($login_result['detail']);
       if (empty($login_result['id'])) throw new \Exception("NO HAY ID DE SESION.");
      
       $output->writeln("OK AUTENTICACION - SESSION ID: ".$login_result['id']);
       $this->sessionId = $login_result['id'];
      
    }

    private function updateDataBase(InputInterface $input, OutputInterface $output, $serializer) 
    {
    
      $output->writeln("DESCARGANDO CASOS EN CRM");

      //Buscar los casos:
      $get_entry_list_parameters = array(
         'session' => $this->sessionId,
         'module_name' => 'Cases',
         'query' => "cases.type = 'CONSULTA_CLIENTE'",
         'order_by' => "date_entered ASC",
         'offset' => '0',
         'link_name_to_fields_array' => array(
            array('name' => 'contacts', 'value' => array('id','first_name','last_name','email1'))
         ),
         'max_results' => '999',
         'deleted' => '0',
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);
      
       $itemsKeys = array();

       $customerCases = array();
       
       foreach ($result['entry_list'] as $index => $items) {
         //Un nuevo objeto por cada item
         $customerCase = new CustomerCase();
         foreach ($items as $key => $data) {
           $customerCase->setTicket($this->searchInResult(-1,$items,'case_number'));
           $customerCase->setName($this->searchInResult(-1,$items,'name'));
           $customerCase->setCreatedAt(strtotime($this->searchInResult(-1,$items,'date_entered')));
           $customerCase->setStatus($this->searchInResult(-1,$items,'status')); 
           $customerCase->setDescription($this->searchInResult(-1,$items,'description')); 
           $customerCase->setResolution($this->searchInResult(-1,$items,'resolution')); 
         }
         $customerCases[$index] = $customerCase;
       }

       //Con esto me aseguro que solo "bajen" los casos que tienen creados los contactos.
       foreach ($result['relationship_list'] as $index => $items) {
         foreach ($items['link_list'] as $data) {
           foreach ($data['records'] as $info) {
             $email = $this->searchInLinkedResult(-1,$info,'email1');
             $customerCase = $customerCases[$index];
             $customerCase->setEmail($email);
             //Ahora guardo el objeto en Json.
             $output->writeln("GUARDANDO CASO #".$customerCase->getTicket()." EN LOCAL");
             $file = $this->getContainer()->get('kernel')->getRootDir().'/Resources/data/server/case-'.$customerCase->getTicket().'-data.json';
             file_put_contents($file, $serializer->serialize($customerCase, 'json'));
           }
         }
       }




  }

  protected function searchInResult($item = -1,$result = array(), $flag = 'account_id') {
    foreach ($result['name_value_list'] as $key => $item) {
      if ($item['name'] == $flag) {
        return utf8_encode($item['value']);
      }
    }
    throw new \Exception("FILTRO NO ENCONTRADO");
  }

  protected function searchInLinkedResult($item = -1,$result = array(), $flag = 'account_id') {
    foreach ($result['link_value'] as $key => $item) {
      if ($item['name'] == $flag) {
        return utf8_encode($item['value']);
      }
    }
    throw new \Exception("FILTRO NO ENCONTRADO");
  }
  
  
}