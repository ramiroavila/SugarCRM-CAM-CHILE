<?php

namespace Cam\PanelCrmBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlarmasCrmCommand extends ContainerAwareCommand
{

    protected $client = null;
    protected $sessionId = -1;

    protected function configure()
    {
        $this
            ->setName('crm:alarmas')
            ->setDescription('GENERACION DE ALARMAS CRM CAM');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("CONECTANDO A WEBSERVICE VIA SOAP");

        try {

         //retrieve WSDL
         $this->client = new \nusoap_client("http://crm.cam-la.com/service/v4/soap.php?wsdl", 'true');

         $this->executeLogin($input,$output);

         //Primera alarma:
         $this->executePrimeraAlarma($input,$output);

         //Segunda alarma:
         $this->executeSegundaAlarma($input,$output);

         //Tercera alarma:
         $this->executeTerceraAlarma($input,$output);

         //Cuarta alarma:
         $this->executeCuartaAlarma($input,$output);

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
              'password' => md5($this->getContainer()->getParameter('passwd')),
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

    /**
    * Alarma 1
    *   Cuándo:  No hay actualización de la oportunidad durante 2 semanas
    *   Destinatario: Responsable Comercial de la oportunidad y Claudio Poblete
    *   Tipo de alarma: correo electrónico  a destinatarios y en registro Casos (que deben cerrarse)
    */

    private function executePrimeraAlarma(InputInterface $input, OutputInterface $output) {

       $output->writeln("EJECUTANDO PRIMERA ALARMA.");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         //The SQL WHERE clause without the word "where".
         'query' => "sales_stage IN ('APROBADO','PROSPECCION_GENERAL','PROSPECCION_CONTINGENTE','EN_ESTUDIO') AND TIMESTAMPDIFF(DAY,opportunities.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') > 14 AND YEAR(date_closed) = YEAR(NOW())",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '999',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de oportunidades, veo ahora si si han modificado otras cosas como REUNIONES, ETC.
       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." OPORTUNIDADES.");

       //Tareas que hayan sido de las ultimas semanas???
       $this->searchForTasks($itemsKeys, $now, $output);

       //Reuniones que hayan sido de las ultimas semanas???
       $this->searchForMeetings($itemsKeys, $now, $output);

       //Llamadas que hayan sido de las ultimas semanas???
       $this->searchForCalls($itemsKeys, $now, $output);

       //Historial - Notes que hayan sido de las ultimas semanas???
       $this->searchForNotes($itemsKeys, $now, $output);

       //documents_opportunities
       $this->searchForDocuments($itemsKeys, $now, $output);

       //opportunities_contacts
       $this->searchForContacts($itemsKeys, $now, $output);

       //casos
       $this->searchForCases($itemsKeys, $now, $output);


       //Ahora creo un caso por cada oportunidad no modificada en los ultimas 2 semanas.
       $output->writeln("SE NOTIFICARÁ: ".count($itemsKeys)." OPORTUNIDADES.");

       foreach ($itemsKeys as $item) {

         if (isset($result['entry_list'])) {

          $resultItem = $this->filterArray($result['entry_list'],$item);

          $subject = "OPORTUNIDAD ".$this->searchInResult($item,$resultItem,'name')." SIN ACTUALIZACIÓN EN LOS ÚLTIMOS 14 DÍAS";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_1.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'cliente' => $this->searchInResult($item,$resultItem,'account_name'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');

          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'account_id')),
              array("name" => "assigned_user_id", "value" => $assigned_user_id),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);
          $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Relationship
          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' => $resultCase['id'],
            'link_field_name' => 'opportunities_cases_1',
            'related_ids' => array(
              $this->searchInResult($item,$resultItem,'id')
            ),
            'deleted'=> 0,
          );

          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);

          //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #1: ".$subject)
            ->setFrom(array('info@bctic.net' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }
       }

       $output->writeln("OK PRIMERA ALARMA");
  }

      /**
      *
      Alarma 2
        Cuándo:  7 días antes de cumplirse hitos “Fecha de Entrega” y “Fecha de Adjudicación”
        Destinatario: Responsable Comercial de la oportunidad
        Registro: Correo electrónico
      */

    private function executeSegundaAlarma(InputInterface $input, OutputInterface $output) {

       $output->writeln("EJECUTANDO SEGUNDA ALARMA.");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         //The SQL WHERE clause without the word "where".
         'query' => "sales_stage IN ('APROBADO','PROSPECCION_GENERAL','PROSPECCION_CONTINGENTE','EN_ESTUDIO') AND ( (TIMESTAMPDIFF(DAY,fecha_de_entrega_c, '".date_format($now, 'Y-m-d')."') = -7 ) OR ( TIMESTAMPDIFF(DAY,date_closed, '".date_format($now, 'Y-m-d')."') = -7 ) ) AND YEAR(date_closed) = YEAR(NOW())",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '200',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de oportunidades, veo ahora si si han modificado otras cosas como REUNIONES, ETC.
       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." OPORTUNIDADES.");

       //Ahora creo un caso por cada oportunidad no modificada en los ultimas 2 semanas.
       $output->writeln("SE NOTIFICARÁ A: ".count($itemsKeys)." OPORTUNIDADES.");

       foreach ($itemsKeys as $item) {

         if (isset($result['entry_list'])) {

          $resultItem = $this->filterArray($result['entry_list'],$item);

          $subject = "OPORTUNIDAD ".$this->searchInResult($item,$resultItem,'name').": FECHA DE ENTREGA O ADJUDICACIÓN EN 7 DÍAS";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_2.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'cliente' => $this->searchInResult($item,$resultItem,'account_name'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');

          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'account_id')),
              array("name" => "assigned_user_id", "value" => $this->searchInResult($item,$resultItem,'assigned_user_id')),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);
          $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Relationship
          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' => $resultCase['id'],
            'link_field_name' => 'opportunities_cases_1',
            'related_ids' => array(
              $this->searchInResult($item,$resultItem,'id')
            ),
            'deleted'=> 0,
          );

          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);

           //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #2: ".$subject)
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }
       }

       $output->writeln("OK SEGUNDA ALARMA");
  }

      /**
      *
       * Alarma 3
       * Cuándo:  1 día después de cumplirse hitos “Fecha de Entrega” y “Fecha de Adjudicación” si es que no se ha modificado el estado de la oportunidad
       *  Destinatario: Responsable Comercial de la oportunidad, gerente comercial  y Claudio Poblete
       *
       *

       Te mando los criterios para validar el cambio de status de una oportunidad.
Para eso hay que crear las siguientes categorías de documentos: selectividad, propuesta y adjudicación -- OK

a.- Pasar a “Estudio”: Debe tener el documento “selectividad”. Esto es válido para todas las oportunidades cuyo monto sea superior a 250 mil dólares
b.- Pasar a “Aprobado”: Debe tener el documento “propuesta”. Esto es válido para todas las ofertas
c.- Pasar a “Adjudicado”, “Perdida” o “Desierta”: Debe tener el documento “adjudicación”.

      *  Registro: correo electrónico  a destinatarios y en registro Casos (que deben cerrarse)
      */

    private function executeTerceraAlarma(InputInterface $input, OutputInterface $output) {

      $output->writeln("EJECUTANDO TERCERA ALARMA.");
      $this->alarmaPasoAEstudio($input,$output);
      $this->alarmaAprobado($input,$output);
      //$this->alarmaAdjudicadoPerdidaDesierta($input,$output);
      $output->writeln("OK TERCERA ALARMA");

    }

    private function alarmaPasoAEstudio(InputInterface $input, OutputInterface $output) {
       $output->writeln("EJECUTANDO TERCERA ALARMA: PASO A ESTUDIO");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         //The SQL WHERE clause without the word "where".
         'query' => "sales_stage IN ('EN_ESTUDIO') AND amount >= 250 AND ( (TIMESTAMPDIFF(DAY,fecha_de_entrega_c, '".date_format($now, 'Y-m-d')."') > -8 ) OR ( TIMESTAMPDIFF(DAY,date_closed, '".date_format($now, 'Y-m-d')."') > -8 ) ) AND YEAR(date_closed) = YEAR(NOW())",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '200',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de oportunidades de esta alarma - tiene el documento de selectividad:


       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." OPORTUNIDADES.");

       foreach ($itemsKeys as $item) {

         $resultItem = $this->filterArray($result['entry_list'],$item);

         if ($this->searchForRelatedDocuments($item, 'SELECTIVIDAD', $output)) {
           //Tiene documento de SELECTIVIDAD, NO HAGO NADA
         } else {
           //No tiene documento de SELECTIVIDAD:
           $output->writeln(" OPORTUNIDAD NO TIENE DOCUMENTO DE SELECTIVIDAD, SE CREA CASO Y AVISA POR EMAIL");

           $subject = "OPORTUNIDAD ".$this->searchInResult($item,$resultItem,'name')." EN ESTADO ESTUDIO NO TIENE DOCUMENTO DE SELECTIVIDAD";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_3.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'cliente' => $this->searchInResult($item,$resultItem,'account_name'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');

          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'account_id')),
              array("name" => "assigned_user_id", "value" => $this->searchInResult($item,$resultItem,'assigned_user_id')),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);
          $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Relationship
          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' => $resultCase['id'],
            'link_field_name' => 'opportunities_cases_1',
            'related_ids' => array(
              $this->searchInResult($item,$resultItem,'id')
            ),
            'deleted'=> 0,
          );

          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);

           //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #3: ".$subject)
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }
       }
  }

      private function alarmaAprobado(InputInterface $input, OutputInterface $output) {
       $output->writeln("EJECUTANDO TERCERA ALARMA: APROBADO");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         //The SQL WHERE clause without the word "where".
         'query' => "sales_stage IN ('APROBADO') AND YEAR(date_closed) = YEAR(NOW())",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '200',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de oportunidades de esta alarma - tiene el documento de selectividad:


       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." OPORTUNIDADES.");

       foreach ($itemsKeys as $item) {

         $resultItem = $this->filterArray($result['entry_list'],$item);

         if ($this->searchForRelatedDocuments($item, 'PROPUESTA', $output)) {
           //Tiene documento de PROPUESTA, NO HAGO NADA
         } else {
           //No tiene documento de PROPUESTA:
           $output->writeln(" OPORTUNIDAD NO TIENE DOCUMENTO DE PROPUESTA, SE CREA CASO Y AVISA POR EMAIL");

           $subject = "OPORTUNIDAD ".$this->searchInResult($item,$resultItem,'name')." EN ESTADO APROBADO NO TIENE DOCUMENTO DE PROPUESTA";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_3_propuesta.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'cliente' => $this->searchInResult($item,$resultItem,'account_name'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');

          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'account_id')),
              array("name" => "assigned_user_id", "value" => $this->searchInResult($item,$resultItem,'assigned_user_id')),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);
          $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Relationship
          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' => $resultCase['id'],
            'link_field_name' => 'opportunities_cases_1',
            'related_ids' => array(
              $this->searchInResult($item,$resultItem,'id')
            ),
            'deleted'=> 0,
          );

          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);

           //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #3: ".$subject)
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }
       }
  }

      private function alarmaAdjudicadoPerdidaDesierta(InputInterface $input, OutputInterface $output) {

       $output->writeln("EJECUTANDO TERCERA ALARMA: ADJUDICADO PERDIDA DESIERTA");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         //The SQL WHERE clause without the word "where".
         'query' => "sales_stage IN ('DESIERTA','Closed Won','Closed Lost') AND YEAR(date_closed) = YEAR(NOW())",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '200',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de oportunidades de esta alarma - tiene el documento de selectividad:


       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." OPORTUNIDADES.");

       foreach ($itemsKeys as $item) {

         $resultItem = $this->filterArray($result['entry_list'],$item);

         if ($this->searchForRelatedDocuments($item, 'ADJUDICACION', $output)) {
           //Tiene documento de PROPUESTA, NO HAGO NADA
         } else {
           //No tiene documento de PROPUESTA:
           $output->writeln(" OPORTUNIDAD NO TIENE DOCUMENTO DE ADJUDICACION, SE CREA CASO Y AVISA POR EMAIL");

           $subject = "OPORTUNIDAD ".$this->searchInResult($item,$resultItem,'name')." EN ESTADO ADJUDICADO, PERDIDA O DESIERTA NO TIENE DOCUMENTO DE ADJUDICACIÓN";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_3_adjudicado_perdida_desierta.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'cliente' => $this->searchInResult($item,$resultItem,'account_name'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');


          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'account_id')),
              array("name" => "assigned_user_id", "value" => $this->searchInResult($item,$resultItem,'assigned_user_id')),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);
          $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Relationship
          $set_relationship_parameters = array(
            'session' => $this->sessionId,
            'module_name' => 'Cases',
            'module_id' => $resultCase['id'],
            'link_field_name' => 'opportunities_cases_1',
            'related_ids' => array(
              $this->searchInResult($item,$resultItem,'id')
            ),
            'deleted'=> 0,
          );

          $resultRelationship = $this->client->call('set_relationship', $set_relationship_parameters);

           //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #3: ".$subject)
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }
       }
  }


     /**
    * Alarma 4
    *   Cuándo:  Hay Clientes con el mismo RUT
    *   Destinatario: Bernardita Duque
    *   Tipo de alarma: correo electrónico a destinatarios y en registro Casos (que deben cerrarse)
    */

    private function executeCuartaAlarma(InputInterface $input, OutputInterface $output) {

       $output->writeln("EJECUTANDO CUARTA ALARMA.");

       $now = date_create();

       $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Accounts',
         //The SQL WHERE clause without the word "where".
         'query' => "LENGTH(accounts.ticker_symbol) > 0",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "date_entered ASC",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '999',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Results es el listado de clientes
       $itemsKeys = array();
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $data) {
          $itemsKeys[$items['id']] = $items['id'];
         }
       }

       $output->writeln("SE BUSCARÁ INFORMACION DE: ".count($itemsKeys)." CLIENTES.");


       //Ahora creo un caso por cada oportunidad no modificada en los ultimas 2 semanas.
       $output->writeln("SE NOTIFICARÁ: ".count($itemsKeys)." CLIENTES.");

       $ruts = array();

       foreach ($itemsKeys as $item) {

         if (isset($result['entry_list'])) {

          $resultItem = $this->filterArray($result['entry_list'],$item);

          $rut = $this->searchInResult($item,$resultItem,'ticker_symbol');

          if (isset($ruts[$rut])) {
            //Notifico
          } else {
            //No notifico, solo creo
            $ruts[$rut] = $rut;
            continue;
          }



          $subject = "CLIENTE RUT ".$this->searchInResult($item,$resultItem,'name')." TIENE RUT ".$rut." DUPLICADO.";

          $body = $this->getContainer()->get('templating')->render(
                'PanelCrmBundle:Default:notificacion_alarma_4.html.twig',
                 array('titulo' => $this->searchInResult($item,$resultItem,'name'),
                       'usuario' => $this->searchInResult($item,$resultItem,'assigned_user_name'),
                       'rut' => $this->searchInResult($item,$resultItem,'ticker_symbol'),
                       )
              );

          $assigned_user_id = $this->searchInResult($item,$resultItem,'assigned_user_id');

          $set_entry_parameters = array(
            "session" => $this->sessionId,
            "module_name" => "Cases",
            //Record attributes
            "name_value_list" => array(
              //to update a record, you will nee to pass in a record id as commented below
              //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
              array("name" => "name", "value" => $subject),
              array("name" => "description", "value" => $body),
              array("name" => "status", "value" => 'Assigned'),
              array("name" => "account_id", "value" => $this->searchInResult($item,$resultItem,'id')),
              array("name" => "assigned_user_id", "value" => $assigned_user_id),
            ),
          );

          $output->writeln("CREANDO CASO PARA ID: ".$item);

         $resultCase = $this->client->call('set_entry', $set_entry_parameters);

          //Buscar email asignado:
          $emailAsignado = $this->searchForAssignedUserIdEmail($assigned_user_id, $output);

          $body .= 'Link al caso: http://crm.cam-la.com/index.php?module=Cases&action=DetailView&record='.$resultCase['id'];

          $output->writeln("ENVIANDO EMAIL A: ".$assigned_user_id.' '.$emailAsignado['name'].' '.$emailAsignado['email']);
          //Copia a Claudio Poblete y al Responsable comercial de la .
          $message = \Swift_Message::newInstance()
            ->setSubject("ALARMA CRM #1: ".$subject)
            ->setFrom(array('crm@crm.cam-la.com' => 'CRM CAM-LA Chile'))
            ->setTo($emailAsignado['email'])
            ->setCc(array('bdn@cam-la.com','cpp@cam-la.com'))
            //->setBcc('lbarr006@gmail.com')
            ->setBody($body)
          ;

          $this->getContainer()->get('mailer')->send($message);

         }

       }

       $output->writeln("OK CUARTA ALARMA");
  }



  protected function filterArray($data,$id) {
    foreach ($data as $key => $item) {
      if ($item['id'] == $id) {
        return $item;
      }
    }
    return array();
  }

  protected function searchInResult($item = -1,$result = array(), $flag = 'account_id') {
    foreach ($result['name_value_list'] as $key => $item) {
      if ($item['name'] == $flag) {
        return utf8_encode($item['value']);
      }
    }
    throw new \Exception("FILTRO NO ENCONTRADO");
  }

  /**
  *
  */
  private function searchForAssignedUserIdEmail($uid, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE EMAIL DE USUARIO ".$uid." ASIGNADO A OPORTUNIDAD.");

         $get_entry_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Users',
         //The SQL WHERE clause without the word "where".
         'id' => $uid,
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry', $get_entry_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       return array(
                     'email' => $this->searchInResult(-1, $result['entry_list'][0], 'email1'),
                     'name' => $this->searchInResult(-1, $result['entry_list'][0], 'full_name'),
                    );

  }

  /**
  * SI HAY ALGUNA TAREA DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForTasks(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE TAREAS RECIENTES.");

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Tasks',
         //The SQL WHERE clause without the word "where".
         'query' => "parent_type = 'Opportunities' AND parent_id IN ('".implode("','",array_keys($itemsKeys))."') AND (TIMESTAMPDIFF(DAY,tasks.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,tasks.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '99',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $values) {
           if (is_array($values)) {
             foreach($values as $keyValue => $dataValue) {
                if ($dataValue['name'] == 'parent_id') {
                  //Elimina del KeyIndex
                  unset($itemsKeys[$dataValue['value']]);
                  $output->writeln(" * TAREA RECIENTE ENCONTRADA.");
                }
             }
           }
         }
       }

  }

    /**
  * SI HAY ALGUNA REUNION DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForMeetings(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE REUNIONES RECIENTES.");

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Meetings',
         //The SQL WHERE clause without the word "where".
         'query' => "parent_type = 'Opportunities' AND parent_id IN ('".implode("','",array_keys($itemsKeys))."') AND (TIMESTAMPDIFF(DAY,meetings.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,meetings.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '99',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $values) {
           if (is_array($values)) {
             foreach($values as $keyValue => $dataValue) {
                if ($dataValue['name'] == 'parent_id') {
                  //Elimina del KeyIndex
                  unset($itemsKeys[$dataValue['value']]);
                  $output->writeln(" * REUNION RECIENTE ENCONTRADA.");
                }
             }
           }
         }
       }

  }

      /**
  * SI HAY ALGUNA LLAMADA DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForCalls(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE LLAMADAS RECIENTES.");

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Calls',
         //The SQL WHERE clause without the word "where".
         'query' => "parent_type = 'Opportunities' AND parent_id IN ('".implode("','",array_keys($itemsKeys))."') AND (TIMESTAMPDIFF(DAY,calls.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,calls.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '99',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $values) {
           if (is_array($values)) {
             foreach($values as $keyValue => $dataValue) {
                if ($dataValue['name'] == 'parent_id') {
                  //Elimina del KeyIndex
                  unset($itemsKeys[$dataValue['value']]);
                  $output->writeln(" * LLAMADA RECIENTE ENCONTRADA.");
                }
             }
           }
         }
       }

  }

      /**
  * SI HAY ALGUNA NOTA DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForNotes(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE NOTAS/HISTORIAL RECIENTES.");

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Notes',
         //The SQL WHERE clause without the word "where".
         'query' => "parent_type = 'Opportunities' AND parent_id IN ('".implode("','",array_keys($itemsKeys))."') AND (TIMESTAMPDIFF(DAY,notes.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,notes.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => "",
         //The record offset from which to start.
         'offset' => '0',
         //Optional. A list of fields to include in the results.
/*         'select_fields' => array(
              'id',
              'name',
              'title',
         ), */
         'link_name_to_fields_array' => array(
         ),
         //The maximum number of results to return.
         'max_results' => '99',
         //To exclude deleted records
         'deleted' => '0',
         //If only records marked as favorites should be returned.
         'Favorites' => false,
       );

       $result = $this->client->call('get_entry_list', $get_entry_list_parameters);
       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         foreach ($items as $key => $values) {
           if (is_array($values)) {
             foreach($values as $keyValue => $dataValue) {
                if ($dataValue['name'] == 'parent_id') {
                  //Elimina del KeyIndex
                  unset($itemsKeys[$dataValue['value']]);
                  $output->writeln(" * NOTA RECIENTE ENCONTRADA.");
                }
             }
           }
         }
       }

  }

  /**
  * SI HAY ALGUN DOCUMENTO DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForDocuments(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE DOCUMENTOS RECIENTES.");

      foreach ($itemsKeys as $itemKey) {

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         'module_id' => $itemKey,
         'link_field_name' => 'documents',
         'related_module_query' => "documents.deleted = 0 AND (TIMESTAMPDIFF(DAY,documents.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,documents.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The related fields to be returned.
         'related_fields' => array('id','name'),
         'related_module_link_name_to_fields_array' => array(),
         //To exclude deleted records
         'deleted'=> 0,
         'order_by' => '',
         'offset' => 0,
         'limit' => 200,
       );

       $result = $this->client->call('get_relationships', $get_entry_list_parameters);

       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         //Elimina del KeyIndex
         unset($itemsKeys[$itemKey]);
         $output->writeln(" * DOCUMENTO RECIENTE ENCONTRADO.");
       }

    }

  }

  /**
  * DEVUELVE BOOLEAN SI LOS DOCUMENTOS DE LA OPORTUNIDAD EXISTEN
  *
  */
  private function searchForRelatedDocuments($itemKey, $category = '',OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE DOCUMENTOS PARA LA OPORTUNIDAD #".$itemKey);


         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         'module_id' => $itemKey,
         'link_field_name' => 'documents',
         'related_module_query' => "documents.deleted = 0 AND  documents.category_id = '".$category."'",
         //The related fields to be returned.
         'related_fields' => array('id','name'),
         'related_module_link_name_to_fields_array' => array(),
         //To exclude deleted records
         'deleted'=> 0,
         'order_by' => '',
         'offset' => 0,
         'limit' => 200,
       );

       $result = $this->client->call('get_relationships', $get_entry_list_parameters);

       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         return true;
       }
      return false;
  }

  /**
  * SI HAY ALGUN CONTACTO DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForContacts(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE CONTACTOS RECIENTES.");

      foreach ($itemsKeys as $itemKey) {

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         'module_id' => $itemKey,
         'link_field_name' => 'contacts',
         'related_module_query' => "contacts.deleted = 0 AND (TIMESTAMPDIFF(DAY,contacts.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,contacts.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The related fields to be returned.
         'related_fields' => array('id','name'),
         'related_module_link_name_to_fields_array' => array(),
         //To exclude deleted records
         'deleted'=> 0,
         'order_by' => '',
         'offset' => 0,
         'limit' => 200,
       );

       $result = $this->client->call('get_relationships', $get_entry_list_parameters);

       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         //Elimina del KeyIndex
         unset($itemsKeys[$itemKey]);
         $output->writeln(" * CONTACTO RECIENTE ENCONTRADO.");
       }

    }

  }

    /**
  * SI HAY ALGUN CASO DE LA OPORTUNIDAD DESDE SER ELIMINADO EL ID DEL ITEMSKEY
  *
  */
  private function searchForCases(&$itemsKeys,$now, OutputInterface $output) {
    $output->writeln("EJECUTANDO BUSQUEDA DE CASOS RECIENTES.");

      foreach ($itemsKeys as $itemKey) {

         $get_entry_list_parameters = array(

         //session id
         'session' => $this->sessionId,
         //The name of the module from which to retrieve records
         'module_name' => 'Opportunities',
         'module_id' => $itemKey,
         'link_field_name' => 'opportunities_cases_1',
         'related_module_query' => "cases.deleted = 0 AND cases.status IN ('Assigned') AND (TIMESTAMPDIFF(DAY,cases.date_modified, '".date_format($now, 'Y-m-d H:i:s')."') < 14 OR TIMESTAMPDIFF(DAY,cases.date_entered, '".date_format($now, 'Y-m-d H:i:s')."') < 14 )",
         //The related fields to be returned.
         'related_fields' => array('id','name'),
         'related_module_link_name_to_fields_array' => array(),
         //To exclude deleted records
         'deleted'=> 0,
         'order_by' => '',
         'offset' => 0,
         'limit' => 200,
       );

       $result = $this->client->call('get_relationships', $get_entry_list_parameters);

       if (isset($result['faultstring'])) throw new \Exception($result['detail']);

       //Ahora que existe algo reciente para este item debo sacarlo del lista de indices:
       foreach ($result['entry_list'] as $items) {
         //Elimina del KeyIndex
         unset($itemsKeys[$itemKey]);
         $output->writeln(" * CASO RECIENTE ENCONTRADO.");
       }

    }

  }

}
