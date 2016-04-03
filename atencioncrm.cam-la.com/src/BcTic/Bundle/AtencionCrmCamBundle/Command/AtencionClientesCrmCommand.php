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



class AtencionClientesCrmCommand extends ContainerAwareCommand
{

    protected $client = null;
    protected $sessionId = -1;

    protected function configure()
    {
        $this
            ->setName('crm:atencion-clientes')
            ->setDescription('CARGA CASOS EN CRM');
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

          //Obtengo todos los archivos que hay en la carpeta:
          $path = $this->getApplication()->getKernel()->getContainer()->get('kernel')->getRootDir().'/Resources/data/';
          $files = scandir($path);
          foreach ($files as $file) {
            if (is_readable($path.$file) == false ) continue;
            if (is_file($path.$file) == false ) continue;
            $output->writeln("EJECUTANDO DATA ".$file);
            $content = file_get_contents($path.$file);
            $data = json_decode(json_encode($content),true);
            $obj = $serializer->deserialize($data, 'BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase','json');
            $this->uploadToDataBase($obj, $input, $output);

            //Ahora debo eliminar el archivo, pues con otro comando los obtengo y los voy actualizando para consulta cada 5 minutos.
            unlink($path.$file);
          }
        
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

    private function uploadToDataBase(CustomerCase $customerCase, InputInterface $input, OutputInterface $output) 
    {
    
      $output->writeln("CREANDO CASO EN CRM");

      $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              array("name" => "name", "value" => "CASO DE ATENCIÓN CLIENTE ".date('d-m-Y h:j',$customerCase->getCreatedAt())),
              array("name" => "description", "value" => $customerCase->getDescription()),
              array("name" => "status", "value" => $customerCase->getStatus()),
              array("name" => "type", "value" => $customerCase->getType()),
              array("name" => "date_entered", "value" => date('Y-m-d h:j:s',$customerCase->getCreatedAt())),
              array("name" => "priority", "value" => 'P1'),
            ),
      );

      $resultCase = $this->client->call('set_entry', $set_entry_parameters);
      $output->writeln(" >>> CASO CREADO ID ".$resultCase['id']);


      //Buscar el cliente:
      $get_entry_list_parameters = array(
         'session' => $this->sessionId,
         'module_name' => 'Accounts',
         'query' => "accounts.name LIKE '".$customerCase->getCompany()."%'",
         'order_by' => "date_entered ASC",
         'offset' => '0',
         'link_name_to_fields_array' => array(
         ),
         'max_results' => '1',
         'deleted' => '0',
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);
      
       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

      if (count($itemsKeys) == 0) {
        //Creo la empresa como cuenta cliente
        //y cargo el id como relationship al caso
        $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Accounts",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              array("name" => "name", "value" => $customerCase->getCompany()),
              array("name" => "date_entered", "value" => date('Y-m-d h:j:s',$customerCase->getCreatedAt())),
            ),
      );

        $resultAccount = $this->client->call('set_entry', $set_entry_parameters);
        $output->writeln(" >>> CLIENTE CREADO ID ".$resultAccount['id']);
        $itemsKeys[$resultAccount['id']] = $resultAccount['id'];
      }

      foreach ($itemsKeys as $accountId) {
      $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Accounts',
            'module_id' => $accountId,
            'link_field_name' => 'cases',
            'related_ids' => array(
              $resultCase['id']
            ),
            'deleted'=> 0,
          );
        
          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters); 
          $output->writeln(" >>> CASO RELACIONADO A CLIENTE OK ".$accountId);
      }

      //El contacto del Caso, lo creo siempre.
      $set_entry_parameters = array( 
            "session" => $this->sessionId,
            "module_name" => "Contacts",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              array("name" => "last_name", "value" => $customerCase->getName()),
              array("name" => "phone_work", "value" => $customerCase->getCustomerPhone()),
              array("name" => "email1", "value" => $customerCase->getEmail()),
              array("name" => "title", "value" => $customerCase->getCompanyRole()),
              array("name" => "department", "value" => $customerCase->getCompanyArea()),
              array("name" => "description", "value" => "Contacto creado a través del formulario web de atención clientes"),
              array("name" => "date_entered", "value" => date('Y-m-d h:j:s',$customerCase->getCreatedAt())),
            ),
      );

      $resultContact = $this->client->call('set_entry', $set_entry_parameters);
      $output->writeln(" >>> CONTACTO DEL CASO CREADO ID ".$resultContact['id']);

      $contactId = $resultContact['id'];

      $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Contacts',
            'module_id' => $contactId,
            'link_field_name' => 'cases',
            'related_ids' => array(
              $resultCase['id']
            ),
            'deleted'=> 0,
          );
        
      $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);
      $output->writeln(" >>> CASO RELACIONADO A CONTACTO OK ".$contactId);

      //Ahora pongo el documento:
      if (strlen($customerCase->getUploadedFile()) > 0) {

          $set_entry_parameters = array( 
            "session" => $this->sessionId,
            "module_name" => "Notes",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              array("name" => "name", "value" => "Adjunto vía Form Web Atención clientes"),
              array("name" => "date_entered", "value" => date('Y-m-d h:j:s',$customerCase->getCreatedAt())),
            ),
          );

          $resultNote = $this->client->call('set_entry', $set_entry_parameters);
          $output->writeln(" >>> NOTA DEL CASO CREADO ID ".$resultNote['id']);

          $noteId = $resultNote['id'];

          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' =>  $resultCase['id'],
            'link_field_name' => 'notes',
            'related_ids' => array(
               $noteId
            ),
            'deleted'=> 0,
          );
        
          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);
          $output->writeln(" >>> NOTA RELACIONADO A CASO OK ".$noteId);


          //El adjunto:
          $set_note_attachment_parameters = array(
            "session" => $this->sessionId,
            "note" => array(
              'id' => $noteId,
              'filename' => $customerCase->getUploadedFile(),
              'file' => base64_encode(file_get_contents($customerCase->getFullPathUploadedFile())),
            ),
          );

          $resultAttachments = $this->client->call('set_note_attachment', $set_note_attachment_parameters);
          $output->writeln(" >>> NOTA RELACIONADO A CASO OK ".$noteId);

      }

          $body = $this->getContainer()->get('templating')->render(
                'BcTicAtencionCrmCamBundle:Case:notificacion.html.twig',
                 array('description' => $customerCase->getDescription(),
                       'contact' => $customerCase->getName(),
                       'account' => $customerCase->getCompany(),
                       'link' => 'http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'],
                       )
          );

          $output->writeln("ENVIANDO EMAIL DE AVISO.");
          $message = \Swift_Message::newInstance()
            ->setSubject("NUEVO CASO DE ATENCIÓN CLIENTE")
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo(array('bdn@cam-la.com'))
            ->setCc(array('cpp@cam-la.com'))
            ->setBody($body)
          ;
        
          $this->getContainer()->get('mailer')->send($message);
          //El mensaje para el cliente:

          //Debo obtener el ticket desde el SOAP API
          //Buscar el caso:
          $get_entry_list_parameters = array(
           'session' => $this->sessionId,
           'module_name' => 'Cases',
           'query' => "cases.id = '".$resultCase['id']."'",
           'order_by' => "date_entered ASC",
           'offset' => '0',
           'link_name_to_fields_array' => array(
           ),
           'max_results' => '1',
           'deleted' => '0',
           'Favorites' => false,
          );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);
      
       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         $customerCase->setTicket($this->searchInResult(-1,$items,'case_number'));
       }

        //Ahora notifico al cliente:
        $body = $this->getContainer()->get('templating')->render(
                'BcTicAtencionCrmCamBundle:Case:notificacion_cliente.html.twig',
                 array('description' => $customerCase->getDescription(),
                       'contact' => $customerCase->getName(),
                       'account' => $customerCase->getCompany(),
                       'ticket' => $customerCase->getTicket(),
                       'link' => 'http://atencioncrm.cam-la.com/casos',
                       )
          );

          $output->writeln("ENVIANDO EMAIL DE AVISO A CLIENTE.");
          $message = \Swift_Message::newInstance()
            ->setSubject("NUEVO CASO DE ATENCIÓN CLIENTE")
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($customerCase->getEmail())
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);



  }

   protected function searchInResult($item = -1,$result = array(), $flag = 'account_id') {
    foreach ($result['name_value_list'] as $key => $item) {
      if ($item['name'] == $flag) {
        return utf8_encode($item['value']);
      }
    }
    throw new \Exception("FILTRO NO ENCONTRADO");
  }
  
  
}