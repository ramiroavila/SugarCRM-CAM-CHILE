<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError as FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class OpportunityController extends Controller
{

  /**
   * @Route("/oportunidades/ver-ficha-de-selectividad/{id}.pdf", name="ficha-de-selectividad-pdf" )
   *
   */
  public function showPdfAction($id) {

    $pageUrl = $this->generateUrl('ficha-de-selectividad-html', array('id' => $id), true); // use absolute path!

    return new Response(
        $this->get('knp_snappy.pdf')->getOutput($pageUrl),
        200,
        array(
            'Content-Type'          => 'application/pdf',
            'Content-Disposition'   => 'attachment; filename="'.$id.'.pdf"'
        )
    );
  }

    /**
     * @Route("/oportunidades/ficha-de-selectividad/{id}.html", name="ficha-de-selectividad" )
     * @Template()
     */
    public function showAction($id) {

      //Login
      $client = new \nusoap_client("http://crm.cam-la.com/service/v4/soap.php?wsdl", 'true');
      $login_parameters = array(
        'user_auth' => array(
             'user_name' => 'admin',
             'password' => md5($this->container->getParameter('passwd')),
             'version' => '1'
        ),
        'application_name' => 'SoapAlarmasCRM',
        'name_value_list' => array(),
      );

      $login_result = $client->call('login', $login_parameters);

      if (isset($login_result['faultstring'])) throw new \Exception($login_result['detail']);
      if (empty($login_result['id'])) throw new \Exception("NO HAY ID DE SESION.");

      $sessionId = $login_result['id'];

      //Buscar el cliente:
      $get_entry_list_parameters = array(
         'session' => $sessionId,
         'module_name' => 'Opportunities',
         'query' => "opportunities.id = '".$id."'",
         'order_by' => "date_entered ASC",
         'offset' => '0',
         'link_name_to_fields_array' => array(
         ),
         'max_results' => '1',
         'deleted' => '0',
         'Favorites' => false,
       );

       $result = $client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Parseo la info:
       $info = array();
       foreach ($result['entry_list'] as $items) {
         $info = array();

         foreach ($items['name_value_list'] as $name => $value) {
           $info[$value['name']] = utf8_encode($value['value']);
         }

       }

       //Buscar el cliente:
       $get_entry_list_parameters = array(
          'session' => $sessionId,
          'module_name' => 'Accounts',
          'query' => "accounts.id = '".$info['account_id']."'",
          'order_by' => "date_entered ASC",
          'offset' => '0',
          'link_name_to_fields_array' => array(
          ),
          'max_results' => '1',
          'deleted' => '0',
          'Favorites' => false,
        );

        $result = $client->call('get_entry_list', $get_entry_list_parameters);
        if (isset($result['faultstring'])) throw new \Exception($result['detail']);

        //Parseo la info:
        $accountInfo = array();
        foreach ($result['entry_list'] as $items) {
          $accountInfo = array();

          foreach ($items['name_value_list'] as $name => $value) {
            $accountInfo[$value['name']] = utf8_encode($value['value']);
          }

        }

       //Busco la cuenta:
      return array('entity' => $info, 'account' => $accountInfo);
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
