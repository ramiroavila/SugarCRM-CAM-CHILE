<?php

namespace Cam\PanelCrmBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query\ResultSetMapping;

class DefaultController extends Controller
{

    private $meses = array('Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic');

    protected function acumulate(&$data = array()){
      $i = 1;
      while(isset($data[$i]) && isset($data[$i - 1])) {
        $data[$i] = $data[$i] + $data[$i - 1];
        $i++;
      }     
    }

    /**
    * @Route("/index", name="panel_crm_homepage", options={"expose"=true})
    */
    public function indexAction()
    {

       $annos = array();
       $i = date("Y");
       while($i > 2011) {
         $annos[$i] = $i;  
         $i--;
       }

       $meses = array(
                      '1' => 'Enero', 
                      '2' => 'Febrero', 
                      '3' => 'Marzo', 
                      '4' => 'Abril', 
                      '5' => 'Mayo', 
                      '6' => 'Junio', 
                      '7' => 'Julio', 
                      '8' => 'Agosto', 
                      '9' => 'Septiembre', 
                      '10' => 'Octubre', 
                      '11' => 'Noviembre', 
                      '12' => 'Diciembre', 
                     );
   
       return $this->render('PanelCrmBundle:Default:index.html.twig', array('meses' => $meses, 'annos' => $annos));
    }

    /**
    * @Route("/panel/{anno}/{mes}/index.html", name="panel_crm_layout", requirements={"anno" = "\d+", "mes" = "\d+"} , defaults={"anno" = 2000, "mes" = 1} ,options={"expose"=true})
    */
    public function panelAction($anno,$mes)
    {

       $sql = "SELECT DISTINCT(bu_c) as bu_c FROM opportunities_cstm WHERE bu_c NOT IN ('NULL','UNDEFINED') ORDER BY bu_c;";
       $bus = array();

       $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
       $stmt->execute();
       $bus = $stmt->fetchAll();

       return $this->render('PanelCrmBundle:Default:panel.html.twig', array('anno' => $anno, 'mes' => $mes, 'bus' => $bus ));
    }

    /**
    * @Route("/dashboard/index.html", name="panel_dashboard_crm_layout", requirements={"anno" = "\d+", "mes" = "\d+"} , defaults={"anno" = 2014, "mes" = 1} ,options={"expose"=true})
    */
    public function panelPublicoAction($anno,$mes)
    {
       return $this->render('PanelCrmBundle:Default:panel.html.twig', array('anno' => date('Y'), 'mes' => date('m') ));
    }

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_data_list.html", name="panel_proyeccion_nuevos_ingresos_data_list", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosDataListAction($anno,$mes) {

        $sql = "SELECT MONTH(date_closed) as MES,YEAR(date_closed) as ANNO, amount AS MONTO, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 ORDER BY date_closed DESC, MES DESC;
";


      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:proyeccion_nuevos_ingresos_data_list.html.twig', array('title' => 'Detalle ingresos '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }   

    /**
    * @Route("/{anno}/{mes}/{key}/panel_ingresos_total_bu_data_list.html", name="panel_ingresos_total_bu_data_list", defaults={"anno"="2013","mes"="01","key"="BU"} ,options={"expose"=true})
    */
    public function panelIngresosTotalBuDataListAction($anno,$mes,$key) {

      $bu_c = ($key == 'BU') ? '1' :  ' bu_c = "'.$key.'"';

      $sql = "SELECT MONTH(date_closed) as MES,YEAR(date_closed) as ANNO, amount AS MONTO, opportunities_cstm.bu_c as BU,opportunities.date_closed as ADJUDICACION, (SELECT notes.description FROM notes WHERE parent_id = opportunities.id AND parent_type = 'Opportunities' AND deleted = 0 ORDER BY id DESC LIMIT 1) as last_note_meeting, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 AND ".$bu_c." ORDER BY amount DESC, date_closed DESC, MES DESC";


      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:ingresos_total_bu_data_list.html.twig', array('title' => 'Detalle adjudicación '.$key.' del '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }       

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_proyeccion_data_list.html", name="panel_proyeccion_nuevos_ingresos_proyeccion_data_list", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosProyeccionDataListAction($anno,$mes) {

        $sql = "SELECT MONTH(date_closed) as MES,YEAR(date_closed) as ANNO, amount AS MONTO, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, ((CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 ORDER BY date_closed DESC, MES DESC;
";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:proyeccion_nuevos_ingresos_data_list.html.twig', array('title' => 'Detalle proyección '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }    

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_ingresos_backlog_data_list_data_list.html", name="panel_proyeccion_ingresos_backlog_data_list", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionIngresosBacklogDataListAction($anno,$mes) {

      $sql = "SELECT MONTH(date_closed) as MES, YEAR(date_closed) as ANNO, SUM(opportunities.amount) as MONTO, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, 


      SUM(CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 GROUP BY MES ORDER BY MES ASC;";       

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:proyeccion_ingresos_backlog_data_list.html.twig', array('title' => 'Detalle ingreso backlog generado '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }    

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_data.json", name="panel_proyeccion_nuevos_ingresos_data", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosAction($anno,$mes) {
  
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Proyección nuevos ingresos '.$anno);
      
      //Me conecto a la BD y pregunto por los ingresos del año:
      $ingresos = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $ingresos[] = 0;  
      }

      $sql = "SELECT MONTH(date_closed) as MES, SUM(CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 GROUP BY MES ORDER BY MES ASC;
";     

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $ingresos[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      $ingresos = array_slice($ingresos,0, $mes);

      $this->acumulate($ingresos);
    
      $line_dot = new \OFC_Charts_Line();
      $line_dot->set_values($ingresos);
      $line_dot->set_key( "Ingreso acumulado", 10);
      $line_dot->set_width (1);
      $line_dot->set_colour ('#FF0000');
      $line_dot->set_dot_size (3);


      //Me conecto a la BD y pregunto por la proyeccion del año:
      $proyecciones = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $proyecciones[] = 0;  
      }
 
      $sql = "SELECT MONTH(date_closed) as MES, SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 AND opportunities.deleted = 0 GROUP BY MES ORDER BY MES ASC;
";  

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $proyecciones[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      //La proyección del mes actual es la ultima de los ingresos.
      $proyecciones[$mes - 1] = $proyecciones[$mes - 1] + $ingresos[$mes - 1];

      $this->acumulate($proyecciones);
     
      $line_dot_proyeccion = new \OFC_Charts_Line();
      $line_dot_proyeccion->set_values($proyecciones);
      $line_dot_proyeccion->set_key( "Estimado acumulado", 10);
      $line_dot_proyeccion->set_width (1);
      $line_dot_proyeccion->set_colour ('#41DB00');
      $line_dot_proyeccion->set_dot_size (3);

      //Metas
      $sql = "SELECT enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre FROM metas_metas WHERE anno = ".$anno." AND deleted = 0 and es_marco = 0 LIMIT 1";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $metas = array(0 => 0);

      foreach($stmt->fetchAll() as $data) {
        $metas[0] = (int) $data["enero"];
        $metas[1] = (int) $data["febrero"];
        $metas[2] = (int) $data["marzo"];
        $metas[3] = (int) $data["abril"];
        $metas[4] = (int) $data["mayo"];
        $metas[5] = (int) $data["junio"];
        $metas[6] = (int) $data["julio"];
        $metas[7] = (int) $data["agosto"];
        $metas[8] = (int) $data["septiembre"];
        $metas[9] = (int) $data["octubre"];
        $metas[10] = (int) $data["noviembre"];
        $metas[11] = (int) $data["diciembre"];
      }    
      

      $line_dot_metas = new \OFC_Charts_Line();
      $line_dot_metas->set_values($metas);
      $line_dot_metas->set_key( "Meta año", 10);
      $line_dot_metas->set_width (1);
      $line_dot_metas->set_colour ('#1240AB');
      $line_dot_metas->set_dot_size (3);    

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($this->meses);

      $y = new \OFC_Elements_Axis_Y();

      $max_avg = max($ingresos); 
      $max_avg = ($max_avg > max($proyecciones)) ? $max_avg : max($proyecciones) ;
      $max = ($max_avg > max($metas)) ? $max_avg : max($metas);
      $max = round($max * 1.25,-3);     

      $y->set_range( 0, $max , round($max/4,0) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $line_dot );
      $chart->add_element( $line_dot_proyeccion );
      $chart->add_element( $line_dot_metas );
      $chart->set_x_axis( $x );
      $chart->set_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_backlog_data.json", name="panel_proyeccion_backlog_data", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionBacklogAction($anno,$mes) {

  
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Backlog total generado '.$anno);
      
      //Me conecto a la BD y pregunto por los ingresos del año:
      $ingresos = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $ingresos[] = 0;  
      }

      $sql = "SELECT MONTH(date_closed) as MES, SUM(amount) as TOTAL,SUM(CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 GROUP BY MES ORDER BY MES ASC;
";     

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $ingresos[$data["MES"] - 1] = (int) $data["TOTAL"] - (int) $data["SUMA"];
      }

      $ingresos = array_slice($ingresos,0, $mes);

      $this->acumulate($ingresos);
    
      $line_dot = new \OFC_Charts_Line();
      $line_dot->set_values($ingresos);
      $line_dot->set_key( "Backlog generado acumulado", 10);
      $line_dot->set_width (1);
      $line_dot->set_colour ('#FF0000');
      $line_dot->set_dot_size (3);


      //Me conecto a la BD y pregunto por la proyeccion del año:
      $proyecciones = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $proyecciones[] = 0;  
      }
 
      $sql = "SELECT MONTH(date_closed) as MES, SUM(amount * IFNULL(probabiidad_adjudicacion_c/100,0) ) as TOTAL, IFNULL(probabiidad_adjudicacion_c/100,0) as PROBABILIDAD, SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 AND opportunities.deleted = 0 GROUP BY MES ORDER BY MES ASC;
";  

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $aporte = (int) $data["TOTAL"] - (int) $data["SUMA"];
        $proyecciones[$data["MES"] - 1] = $aporte > 0 ? ($aporte) : 0;

      }

      //La proyección del mes actual es la ultima de los ingresos.
      $proyecciones[$mes - 1] = $proyecciones[$mes - 1] + $ingresos[$mes - 1];

      $this->acumulate($proyecciones);
     
      $line_dot_proyeccion = new \OFC_Charts_Line();
      $line_dot_proyeccion->set_values($proyecciones);
      $line_dot_proyeccion->set_key( "Backlog estimado acumulado", 10);
      $line_dot_proyeccion->set_width (1);
      $line_dot_proyeccion->set_colour ('#41DB00');
      $line_dot_proyeccion->set_dot_size (3);

      //Metas
      $sql = "SELECT monto as monto FROM metas_metas_backlog_anno WHERE anno = ".( $anno )."";    

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $metas = array(0 => 0);

      foreach($stmt->fetchAll() as $data) {
        $metas[0] = (int) $data["monto"];
        $metas[1] = (int) $data["monto"];
        $metas[2] = (int) $data["monto"];
        $metas[3] = (int) $data["monto"];
        $metas[4] = (int) $data["monto"];
        $metas[5] = (int) $data["monto"];
        $metas[6] = (int) $data["monto"];
        $metas[7] = (int) $data["monto"];
        $metas[8] = (int) $data["monto"];
        $metas[9] = (int) $data["monto"];
        $metas[10] = (int) $data["monto"];
        $metas[11] = (int) $data["monto"];
      }    
      

      $line_dot_metas = new \OFC_Charts_Line();
      $line_dot_metas->set_values($metas);
      $line_dot_metas->set_key( "Backlog para ".($anno), 10);
      $line_dot_metas->set_width (1);
      $line_dot_metas->set_colour ('#1240AB');
      $line_dot_metas->set_dot_size (3);    


      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($this->meses);

      $y = new \OFC_Elements_Axis_Y();

      $max_avg = max($ingresos); 
      $max_avg = ($max_avg > max($proyecciones)) ? $max_avg : max($proyecciones) ;
      $max = ($max_avg > max($metas)) ? $max_avg : max($metas);
      $max = round($max * 1.25,-3);     

      $y->set_range( 0, $max , round($max/4,0) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $line_dot );
      $chart->add_element( $line_dot_proyeccion );
      $chart->add_element( $line_dot_metas );
      $chart->set_x_axis( $x );
      $chart->set_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }


    /**
    * @Route("/{anno}/{mes}/panel_ingresos_total_anno_data.json", name="panel_ingresos_total_anno_data", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelIngresosTotalAnnoAction($anno,$mes) {

  
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Venta total '.$anno);
      
      //Me conecto a la BD y pregunto por los ingresos del año:
      $ingresos = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $ingresos[] = 0;  
      }

      $sql = "SELECT MONTH(date_closed) as MES, SUM(amount) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 GROUP BY MES ORDER BY MES ASC;
";     

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $ingresos[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      $ingresos = array_slice($ingresos,0, $mes);

      $this->acumulate($ingresos);
    
      $line_dot = new \OFC_Charts_Line();
      $line_dot->set_values($ingresos);
      $line_dot->set_key( "Ingreso acumulado", 10);
      $line_dot->set_width (1);
      $line_dot->set_colour ('#FF0000');
      $line_dot->set_dot_size (3);


      //Me conecto a la BD y pregunto por la proyeccion del año:
      $proyecciones = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $proyecciones[] = 0;  
      }
 
      $sql = "SELECT MONTH(date_closed) as MES, (amount * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 AND opportunities.deleted = 0 GROUP BY MES ORDER BY MES ASC;
";  

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $proyecciones[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      //La proyección del mes actual es la ultima de los ingresos.
      $proyecciones[$mes - 1] = $proyecciones[$mes - 1] + $ingresos[$mes - 1];

      $this->acumulate($proyecciones);
     
      $line_dot_proyeccion = new \OFC_Charts_Line();
      $line_dot_proyeccion->set_values($proyecciones);
      $line_dot_proyeccion->set_key( "Estimado acumulado", 10);
      $line_dot_proyeccion->set_width (1);
      $line_dot_proyeccion->set_colour ('#41DB00');
      $line_dot_proyeccion->set_dot_size (3);

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($this->meses);

      $y = new \OFC_Elements_Axis_Y();

      $max_avg = max($ingresos); 
      $max_avg = ($max_avg > max($proyecciones)) ? $max_avg : max($proyecciones) ;
      $max = round($max_avg * 1.25,-3);     

      $y->set_range( 0, $max , round($max/4,0) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $line_dot );
      $chart->add_element( $line_dot_proyeccion );
      $chart->set_x_axis( $x );
      $chart->set_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_marco_data_list.html", name="panel_proyeccion_nuevos_ingresos_marco_data_list", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosMarcoDataListAction($anno,$mes) {

        $sql = "SELECT MONTH(date_closed) as MES,YEAR(date_closed) as ANNO, amount AS MONTO, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, ((CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) *  1) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 1 ORDER BY date_closed DESC, MES DESC;
";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:proyeccion_nuevos_ingresos_data_list.html.twig', array('title' => 'Detalle ingresos marco '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }    

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_marco_proyeccion_data_list.html", name="panel_proyeccion_nuevos_ingresos_marco_proyeccion_data_list", defaults={"anno"="2013","mes"="01"} ,options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosMarcoProyeccionDataListAction($anno,$mes) {

        $sql = "SELECT probabiidad_adjudicacion_c,MONTH(date_closed) as MES,YEAR(date_closed) as ANNO, amount AS MONTO, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN , opportunities.name as OPORTUNIDAD, ((CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 1  AND opportunities.deleted = 0 ORDER BY date_closed DESC, MES DESC;
";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:proyeccion_nuevos_ingresos_data_list.html.twig', array('title' => 'Detalle proyección marco '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }  


    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_marco_data.json", name="panel_proyeccion_nuevos_ingresos_marco_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosMarcoAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Proyección ingresos por contratos marco '.$anno);
      
      //Me conecto a la BD y pregunto por los ingresos del año:
      $ingresos = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $ingresos[] = 0;  
      }

      $sql = "SELECT MONTH(date_closed) as MES, SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage = 'Closed Won' AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 1  GROUP BY MES ORDER BY MES ASC;
";     

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $ingresos[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      $ingresos = array_slice($ingresos,0, $mes);
      $this->acumulate($ingresos);
    
      $line_dot = new \OFC_Charts_Line();
      $line_dot->set_values($ingresos);
      $line_dot->set_key( "Ingresos", 10);
      $line_dot->set_width (1);
      $line_dot->set_colour ('#FF0000');
      $line_dot->set_dot_size (3);


      //Me conecto a la BD y pregunto por la proyeccion del año:
      $proyecciones = array();
      //El primer indice lo lleno con ceros:
      foreach ($this->meses as $key => $value) {
        $proyecciones[] = 0;  
      }

      $sql = "SELECT MONTH(date_closed) as MES, SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END)  * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno."  AND opportunities_cstm.contrato_marzo_c = 1 GROUP BY MES ORDER BY MES ASC;
";   

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $proyecciones[$data["MES"] - 1] = (int) $data["SUMA"];
      }

      //La proyección del mes actual es la ultima de los ingresos.
      $proyecciones[$mes - 1] = $proyecciones[$mes - 1] + $ingresos[$mes - 1];

      $this->acumulate($proyecciones);
     
      $line_dot_proyeccion = new \OFC_Charts_Line();
      $line_dot_proyeccion->set_values($proyecciones);
      $line_dot_proyeccion->set_key( "Estimado", 10);
      $line_dot_proyeccion->set_width (1);
      $line_dot_proyeccion->set_colour ('#41DB00');
      $line_dot_proyeccion->set_dot_size (3);

      //Metas
      $sql = "SELECT enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre FROM metas_metas WHERE anno = 2013 AND deleted = 0 AND anno = ".$anno." and es_marco = 1 LIMIT 1";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $metas = array(0 => 0);

      foreach($stmt->fetchAll() as $data) {
        $metas[0] = (int) $data["enero"];
        $metas[1] = (int) $data["febrero"];
        $metas[2] = (int) $data["marzo"];
        $metas[3] = (int) $data["abril"];
        $metas[4] = (int) $data["mayo"];
        $metas[5] = (int) $data["junio"];
        $metas[6] = (int) $data["julio"];
        $metas[7] = (int) $data["agosto"];
        $metas[8] = (int) $data["septiembre"];
        $metas[9] = (int) $data["octubre"];
        $metas[10] = (int) $data["noviembre"];
        $metas[11] = (int) $data["diciembre"];
      }    
      

      $line_dot_metas = new \OFC_Charts_Line();
      $line_dot_metas->set_values($metas);
      $line_dot_metas->set_key( "Metas", 10);
      $line_dot_metas->set_width (1);
      $line_dot_metas->set_colour ('#1240AB');
      $line_dot_metas->set_dot_size (3);    

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($this->meses);

      $y = new \OFC_Elements_Axis_Y();

      $max_avg = max($ingresos); 
      $max_avg = ($max_avg > max($proyecciones)) ? $max_avg : max($proyecciones) ;
      $max = ($max_avg > max($metas)) ? $max_avg : max($metas);
      $max = round($max * 1.25,-3);     

      $y->set_range( 0, $max , round($max/4,0) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $line_dot );
      $chart->add_element( $line_dot_proyeccion );
      $chart->add_element( $line_dot_metas );
      $chart->set_x_axis( $x );
      $chart->set_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;  
    }

    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_backlog_total_data.json", name="panel_proyeccion_nuevos_ingresos_backlog_total_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosBacklogTotalAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Backlog total ');

      $bar = new \OFC_Charts_Bar_Stack();

      //Una stack por año
      $sql = "SELECT YEAR(date_closed) as ANNO, SUM(amount) as TOTAL,SUM( CASE 1 WHEN ( (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(YEAR(date_closed),'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = '".$anno."' GROUP BY ANNO ORDER BY ANNO ASC LIMIT 1;
";         
  
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      $backlog = 0;     
      foreach($stmt->fetchAll() as $data) {
        $backlog = (int) $data["TOTAL"] - (int) $data["SUMA"];
      }

      //Metas
      $sql = "SELECT anno as ANNO,monto as TOTAL FROM metas_metas_backlog_anno WHERE deleted = 0 AND anno = '".$anno."' ORDER BY anno ASC LIMIT 1;";         
  
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      $meta = 0;
      $labels = array();
      foreach($stmt->fetchAll() as $data) {       
        $meta = (int) $data["TOTAL"] - $backlog;
        if ($meta < 0) $meta = 0;
        $bar->set_tooltip( '#x_label#: MU$D #val#<br>Backlog total: MU$D '.number_format($backlog,0,'.',',').'<br>Meta: MU$D '.number_format((int) $data["TOTAL"],0,'.',',').'<br>Gap: MU$D '.number_format($meta,0,'.',','));
      }

      $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value($backlog, '#ff0000'),  new \OFC_Charts_Bar_Stack_Value($meta, '#1240AB')) ); 

      $y = new \OFC_Elements_Axis_Y();
      $max = round((max($backlog, $meta + $backlog)) * 1.15, -3);
      $y->set_range( 0, $max, round($max/3,-3));

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array( array($anno) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

 /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_backlog_total_siguiente_data.json", name="panel_proyeccion_nuevos_ingresos_backlog_total_siguiente_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosBacklogTotalSiguienteAction($anno,$mes) {

      $anno_proyeccion = array();
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'A facturar por año de Ventas generadas en '.($anno));

      $bar = new \OFC_Charts_Bar_Stack();

      $i = 0;
      $backlog =  0;
      $max_backlog = array();
      while($i < 5) {

        $anno_proyeccion[] = (string) ($anno + $i);
        //Una stack por año
        $sql = "SELECT id as ID, YEAR(date_closed) + ".$i." as ANNO, amount as TOTAL,
                opportunities_cstm.fecha_fin_contrato_c as FIN,
                DATEDIFF(CONCAT('".($anno + $i)."','-1-1'),opportunities_cstm.fecha_fin_contrato_c) as DIFERENCIA_ANNO_FIN,
                opportunities.date_closed as WON,
                opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO,
                -1 * DATEDIFF(opportunities_cstm.fecha_de_inicio_ejecucion_c,opportunities_cstm.fecha_fin_contrato_c) as DURACION,
                (DATEDIFF(CONCAT('".($anno + $i)."','-12-31'),opportunities_cstm.fecha_fin_contrato_c)) as DIFERENCIA_FIN, 
                (DATEDIFF(CONCAT('".($anno + $i)."','-1-1'),opportunities_cstm.fecha_de_inicio_ejecucion_c)) as DIFERENCIA_INICIO,
                ( CASE 1 WHEN ( (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(YEAR(date_closed) ,'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount END) as SUMA_REFERENCIA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = '".$anno."' 
                ORDER BY FIN ASC;
               ";         
        
        //Suma es la facturacion del año según las oportunidades ganadas en el año ACTUAL..
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
        $stmt->execute();      
        
        $aporte = 0; 
        foreach($stmt->fetchAll() as $data) {
          $incremento = $this->calcularAporte($data);
          //echo $data['ID'].';'.$data['RESPUESTA'].';'.$data['ANNO'].';'.$data['INICIO'].';'.$data['DIFERENCIA_INICIO'].';'.$data['DIFERENCIA_FIN'].';'.$data['FIN'].';'.number_format($data['TOTAL'],2,',','.').';'.number_format($incremento,2,',','.').'<br/>';
          $aporte = $aporte + $incremento;
        }

        $backlog = $aporte;
        $max_backlog[] = $backlog;

        $bar->set_tooltip( '#x_label#: MU$D #val# ');
        $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value($backlog, '#ff0000')) );           
  
        $i++;
      } 

      $y = new \OFC_Elements_Axis_Y();
      $max = round((max($max_backlog)) * 1.15, -3);
      $y->set_range( 0, $max, round($max/3,-3)); 
      $x = new \OFC_Elements_Axis_X();
    
      $x->set_labels_from_array(array_values($anno_proyeccion));

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }    

    private function calcularAporte(&$data = array()) {
      
      //Si la fecha de término del contrato es menor al año retorno 0;
      if ($data['DIFERENCIA_FIN'] > 365) { 
        $data['RESPUESTA'] = '-1'; 
        return 0; 
      }

      if (is_null($data['DIFERENCIA_FIN']))  { $data['RESPUESTA'] = '-2'; return 0; }
      if ($data['DURACION'] == 0)  { $data['RESPUESTA'] = '-3'; return $data['TOTAL']; }

      //91320f26-10f9-9bd0-9ae2-5303c9700ec3

      //Termina antes del fin de este año, si.
      if (($data['DIFERENCIA_FIN'] >= 0) and ($data['DIFERENCIA_FIN'] <= 365))  { 
         //Empieza durante este año, sí
         if ($data['DIFERENCIA_INICIO'] <= 0)  { 
           $data['RESPUESTA'] = '-4'; 
           $dato = round($data['TOTAL'],2);
           $data['APORTE'] = $dato;
           return $dato;
         } else {
           //Ya terminó
           $data['RESPUESTA'] = '-5'; 
           $dato = round($data['TOTAL'] * ((-1 * $data['DIFERENCIA_ANNO_FIN'])/$data['DURACION']),2);;
           $data['APORTE'] = $dato;
           return $dato;
         }
      } 

      //Empezó este año y termina en otro año
      if (($data['DIFERENCIA_INICIO'] < 0) and ($data['DIFERENCIA_FIN'] < 0 ))  { 
         $data['RESPUESTA'] = '-6'; 
         $dato = round($data['TOTAL'] * ( (365 - (-1 * $data['DIFERENCIA_INICIO']))/$data['DURACION']),2);;
         $data['APORTE'] = $dato;
         return $dato;
      }


      //Caso ideal - AÑO COMPLETO: 
      $data['RESPUESTA'] = '-8';
      $dato = round($data['TOTAL'] * (365/$data['DURACION']),2);
      $data['APORTE'] = $dato;
      return $dato;
    }
 
    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_backlog_no_enersis_data.json", name="panel_proyeccion_nuevos_ingresos_backlog_no_enersis_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosBacklogNoEnersisAction($anno,$mes) {
    
     $anno_proyeccion = array();
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Backlog Total no Enersis '.($anno));

      $bar = new \OFC_Charts_Bar_Stack();

      //Una stack por año
      $sql = "SELECT YEAR(date_closed) as ANNO, SUM(amount) as TOTAL,SUM( CASE 1 WHEN ( (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(YEAR(date_closed),'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE accounts_cstm.grupo_empresarial_c <> 'ENDESA' AND opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = '".$anno."' GROUP BY ANNO ORDER BY ANNO ASC;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      $backlog = 0;
      foreach($stmt->fetchAll() as $data) {
        $backlog = (int) $data["TOTAL"] - (int) $data["SUMA"];
      }

      //Metas
      $sql = "SELECT anno as ANNO,monto as TOTAL FROM metas_metas_backlog_anno_no_eneris WHERE deleted = 0 AND anno = '".$anno."' ORDER BY anno ASC;";         
  
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      $meta = 0;
      foreach($stmt->fetchAll() as $data) {
        $meta = (int) $data["TOTAL"] - $backlog;
        if ($meta < 0) $meta = 0;
        $bar->set_tooltip( '#x_label#: MU$D #val#<br>Backlog NO ENERSIS: MU$D '.number_format($backlog,0,'.',',').'<br>Meta: MU$D '.number_format((int) $data["TOTAL"],0,'.',',').'<br>Gap: MU$D '.number_format($meta,0,'.',',') );
      }

      ($meta <> 0) ? $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value($backlog, '#ff0000') ,  new \OFC_Charts_Bar_Stack_Value($meta, '#1240AB')) ) : $bar->append_stack( array(new \OFC_Charts_Bar_Stack_Value($backlog, '#ff0000')) ); 

      $y = new \OFC_Elements_Axis_Y();
      $max = round((max($backlog, $meta + $backlog)) * 1.15, -3);
      $y->set_range( 0, $max, round($max/3,-3));

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array( array($anno) );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }


    /**
    * @Route("/{anno}/{mes}/panel_proyeccion_nuevos_ingresos_backlog_no_enersis_siguiente_data.json", name="panel_proyeccion_nuevos_ingresos_backlog_no_enersis_siguiente_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosBacklogNoEnersisSiguienteAction($anno,$mes) {
    
           $anno_proyeccion = array();
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'A facturar NO ENERSIS por año de Ventas generadas en'.($anno));

      $bar = new \OFC_Charts_Bar_Stack();

      $i = 0;
      $backlog =  0;
      $max_backlog = array();
      while($i < 5) {

        $anno_proyeccion[] = (string) ($anno + $i);
        //Una stack por año
        $sql = "SELECT opportunities.id as ID, YEAR(date_closed) + ".$i." as ANNO, amount as TOTAL,
                opportunities_cstm.fecha_fin_contrato_c as FIN,
                DATEDIFF(CONCAT('".($anno + $i)."','-1-1'),opportunities_cstm.fecha_fin_contrato_c) as DIFERENCIA_ANNO_FIN,
                opportunities.date_closed as WON,
                opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO,
                -1 * DATEDIFF(opportunities_cstm.fecha_de_inicio_ejecucion_c,opportunities_cstm.fecha_fin_contrato_c) as DURACION,
                (DATEDIFF(CONCAT('".($anno + $i)."','-12-31'),opportunities_cstm.fecha_fin_contrato_c)) as DIFERENCIA_FIN, 
                (DATEDIFF(CONCAT('".($anno + $i)."','-1-1'),opportunities_cstm.fecha_de_inicio_ejecucion_c)) as DIFERENCIA_INICIO,
                ( CASE 1 WHEN ( (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(YEAR(date_closed) ,'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(YEAR(date_closed) ,'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount END) as SUMA_REFERENCIA 
                FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE accounts_cstm.grupo_empresarial_c <> 'ENDESA' AND opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = '".$anno."' 
                ORDER BY FIN ASC;
               ";         
        
        //Suma es la facturacion del año según las oportunidades ganadas en el año ACTUAL..
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
        $stmt->execute();      
        
        $aporte = 0; 
        foreach($stmt->fetchAll() as $data) {
          $incremento = $this->calcularAporte($data);
          //echo $data['ID'].';'.$data['RESPUESTA'].';'.$data['ANNO'].';'.$data['INICIO'].';'.$data['DIFERENCIA_INICIO'].';'.$data['DIFERENCIA_FIN'].';'.$data['FIN'].';'.number_format($data['TOTAL'],2,',','.').';'.number_format($incremento,2,',','.').'<br/>';
          $aporte = $aporte + $incremento;
        }

        $backlog = $aporte;
        $max_backlog[] = $backlog;

        $bar->set_tooltip( '#x_label#: MU$D #val# ');
        $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value($backlog, '#ff0000')) );           
  
        $i++;
      } 

      $y = new \OFC_Elements_Axis_Y();
      $max = round((max($max_backlog)) * 1.15, -3);
      $y->set_range( 0, $max, round($max/3,-3)); 
      $x = new \OFC_Elements_Axis_X();
    
      $x->set_labels_from_array(array_values($anno_proyeccion));

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;

    }    

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_total_data.json", name="panel_ingresos_total_data", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosTotalAction($anno,$mes,$key,$title) {


      $bu_c = ($key == 'BU') ? '1' :  ' bu_c = "'.$key.'"';
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title($title);

      $bar = new \OFC_Charts_Bar_Stack();

      $ingresos = 0;
      $sql = "SELECT SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = ".$anno." AND ".$bu_c.";"; 

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $ingresos = (int) $data["SUMA"];
      }

      /*$metas = 0;
      $sql = "SELECT IFNULL(metas.monto_1_trim + metas.monto_2_trim + metas.monto_3_trim + metas.monto_4_trim,0) as META FROM metas_metas_por_segmento metas WHERE anno = ".$anno." AND '".$bu_c."' AND metas.deleted = 0 LIMIT 1;"; 

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $metas = (int) $data["META"];
      } */     

      $proyeccion = 0;
      $sql = "SELECT SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND ".$bu_c." LIMIT 1;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      foreach($stmt->fetchAll() as $data) {
        $proyeccion = (int) $data["SUMA"];
      }

      //$label =  '#x_label#: MU$D #val#<br>Ingresos: MU$D '.number_format($ingresos,0,'.',',').'<br>Proyección: MU$D '.number_format((int) $proyeccion,0,'.',',').'<br>Meta: MU$D '.number_format((int) $metas,0,'.',',').'<br>Gap: MU$D '.number_format( ($metas - ($ingresos + $proyeccion) > 0) ? $metas - ($ingresos + $proyeccion) : 0 ,0,'.',',');
      $label =  '#x_label#: MU$D #val#<br>Ingresos: MU$D '.number_format($ingresos,0,'.',',').'<br>Proyección: MU$D '.number_format((int) $proyeccion,0,'.',',');

      //$label_bottom = 'Ingresos: MU$D '.number_format($ingresos,0,'.',',').'<br>Proyección: MU$D '.number_format((int) $proyeccion,0,'.',',').'<br>Meta: MU$D '.number_format((int) $metas,0,'.',',');
      $label_bottom = 'Ingresos: MU$D '.number_format($ingresos,0,'.',',').'<br>Proyección: MU$D '.number_format((int) $proyeccion,0,'.',',');

      $bar->set_tooltip($label);
      $labels = array($label_bottom);

      //$metas = ($metas >= ($ingresos + $proyeccion)) ? $metas - ($ingresos + $proyeccion) : 0 ;

      $items = array();
      //Ingreso
      $items[] = new \OFC_Charts_Bar_Stack_Value($ingresos, '#ff0000');
      if ($proyeccion > 0 ) $items[] = new \OFC_Charts_Bar_Stack_Value($proyeccion, '#41db00'); 
      //if ($metas > 0 ) $items[] = new \OFC_Charts_Bar_Stack_Value($metas, '#1240AB'); 

      $bar->append_stack($items);

      $y = new \OFC_Elements_Axis_Y();
      //$max = round(($ingresos + $proyeccion + $metas) * 1.10, -2);
      $max = round(($ingresos + $proyeccion) * 1.10, -2);
      $y->set_range( 0, $max, round($max/4));

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($labels);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_bu_distribucion_data.json", name="panel_ingresos_distribucion_bu_data", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosBuDistribucionAction($anno,$mes,$key,$title) {


      $bu_c = ($key == 'BU') ? '1' :  ' bu_c = "'.$key.'"';
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title($title);

      $bar = new \OFC_Charts_Bar_Stack();

      $etapas = array(
                      'PROSPECCION_GENERAL' => 'PROSPECC. GRAL.', 
                      'PROSPECCION_CONTINGENTE' => 'PROSPECC. CONTING.',
                      'EN_ESTUDIO' => 'EN ESTUDIO',
                      'APROBADO' => 'APROBADO',
                      'Closed Won' => 'ADJUDICADOS'
                      );

      $valores = array();
      
      foreach ($etapas as $index => $value ) {
      
        //Ingresos 
        $sql = "SELECT SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('".$index."') AND YEAR(date_closed) = ".$anno." AND ".$bu_c.";"; 
        
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        $valor = 0;
        foreach($stmt->fetchAll() as $data) {
          $valor = (int) $data["SUMA"];
          $valores[] = $valor;
        }

        //Nivel 1
        $ingresos = new \OFC_Charts_Bar_Stack_Value($valor, ($index == 'Closed Won') ? '#ff0000' : '#FF4F00');      

        $sql = "SELECT SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND ".$bu_c." LIMIT 1;";

        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        $valor = 0;
        foreach($stmt->fetchAll() as $data) {
          $valor = (int) $data["SUMA"];
          $valores[] = $valor;
        }

        //Nivel 2
        $proyeccion = new \OFC_Charts_Bar_Stack_Value($valor, ($index == 'Closed Won') ? '#41db00' : '#A9E454');  

        $bar->append_stack(array($ingresos,$proyeccion));  
 
      }

      
      //$label =  '#x_label#: MU$D #val#<br>Ingresos: MU$D '.number_format($ingresos,0,'.',',').'<br>Proyección: MU$D '.number_format((int) $proyeccion,0,'.',',');
      //$bar->set_tooltip($label);

      $labels = array_values($etapas);

      $y = new \OFC_Elements_Axis_Y();
      $max = round(max($valores) * 1.20, -2);
      $y->set_range( 0, $max, round($max/4));

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($labels);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_data.json", name="panel_ingresos_data", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosAction($anno,$mes,$key,$title) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( $title);

      $bar = new \OFC_Charts_Bar_Stack();

      $sql = "SELECT QUARTER(date_closed) as TRIMESTRE, SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA, IFNULL(metas.monto_1_trim,0) as META_TRIM_1, IFNULL(metas.monto_2_trim,0) as META_TRIM_2, IFNULL(metas.monto_3_trim,0) as META_TRIM_3, IFNULL(metas.monto_4_trim,0) as META_TRIM_4 FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id LEFT JOIN metas_metas_por_segmento metas ON metas.anno = YEAR(date_closed) AND metas.segmento = segmento_c  WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND metas.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = ".$anno." AND segmento_c = '".$key."' GROUP BY QUARTER(date_closed) ORDER BY QUARTER(date_closed) ASC";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $ingresos = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
      foreach($stmt->fetchAll() as $data) {
        $ingresos[$data["TRIMESTRE"]] = (int) $data["SUMA"];
      }

      $sql = "SELECT IFNULL(metas.monto_1_trim,0) as META_TRIM_1, IFNULL(metas.monto_2_trim,0) as META_TRIM_2, IFNULL(metas.monto_3_trim,0) as META_TRIM_3, IFNULL(metas.monto_4_trim,0) as META_TRIM_4 FROM metas_metas_por_segmento metas WHERE metas.anno = ".$anno." AND metas.segmento = '".$key."'";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $metas = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
      foreach($stmt->fetchAll() as $data) {
        $metas[1] = (int) $data["META_TRIM_1"];
        $metas[2] = (int) $data["META_TRIM_2"];
        $metas[3] = (int) $data["META_TRIM_3"];
        $metas[4] = (int) $data["META_TRIM_4"];
      }  

     $sql = "SELECT QUARTER(date_closed) as TRIMESTRE, SUM( ( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) * IFNULL(probabiidad_adjudicacion_c/100,0) ) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('APROBADO','EN_ESTUDIO','PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') AND YEAR(date_closed) = ".$anno." AND segmento_c = '".$key."' GROUP BY QUARTER(date_closed) ORDER BY QUARTER(date_closed) ASC"; 

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      //Proyeccion
      $proyecciones = array(1 => 0, 2 => 0, 3 => 0, 4 => 0); 
      foreach($stmt->fetchAll() as $data) {
        $proyeccion = (int) $data["SUMA"];        
        $proyecciones[$data["TRIMESTRE"]] = $proyeccion;
      }      

      $trimestres = array();
      $i = 1;
      $max = 0;
      while($i < 5) {
        $ingreso = (isset($ingresos[$i])) ? $ingresos[$i] : 0 ;
        $proyeccion = (isset($proyecciones[$i])) ? $proyecciones[$i] : 0 ;
        $meta = (isset($metas[$i])) ? ($metas[$i] - ($ingreso + $proyeccion)) : 0 ; //Diff
        $items = array();
        $items[] = new \OFC_Charts_Bar_Stack_Value($ingreso, '#ff0000');
        if ($proyeccion > 0 ) $items[] = new \OFC_Charts_Bar_Stack_Value($proyeccion, '#41db00'); 
        if ($meta > 0 ) { $items[] = new \OFC_Charts_Bar_Stack_Value($meta, '#1240AB'); } else { $meta = 0; }
        $bar->append_stack($items);
        $trimestres[] = $i.'º Trim '.$anno.chr(10).'Ingresos: MU$D '.number_format($ingreso,0,'.',',').chr(10).'Proyección: MU$D '.number_format($proyeccion,0,'.',',').chr(10).'Meta: MU$D '.number_format($metas[$i],0,'.',',');
        $max = ($max > ( $meta + $proyeccion + $ingreso )) ? $max : ($meta + $proyeccion + $ingreso) ;
        $i++;
      }

      $bar->set_tooltip( 'MU$D #val#<br>Total: MU$D #total#');

      $y = new \OFC_Elements_Axis_Y();

      //Busco la suma
      $max = round($max * 1.10,-2);
      $y->set_range( 0, $max , round($max/4,0) );

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array( $trimestres );

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_nuevas_oportunidades_data.json", name="panel_ingresos_por_market_manager_nuevas_oportunidades_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerNuevasOportunidadesAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Ingresos nuevas oportunidades '.$anno);

      $bar = new \OFC_Charts_Bar_Stack();
      
      $sql = "SELECT CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO, SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA, (SELECT metas_metas_ingreso_anno_por_agente.monto FROM metas_metas_ingreso_anno_por_agente WHERE metas_metas_ingreso_anno_por_agente.assigned_user_id = users.id AND metas_metas_ingreso_anno_por_agente.anno = YEAR(date_closed) AND metas_metas_ingreso_anno_por_agente.deleted = 0 LIMIT 1) as META FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 0 GROUP BY opportunities.assigned_user_id ORDER BY USUARIO asc";        

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $agentes = array();
      $ingresos = array();
      $proyecciones = array();
      $vals = array(100);
 
      foreach($stmt->fetchAll() as $data) {
         
         if ($data["META"] <> 0) { 
           $gap = ($data["META"] > $data["SUMA"]) ? round( 100 * (($data["META"] - $data["SUMA"]) / $data["META"] ),2) : 0;
         } else { 
           $gap = 0;
         }

         $ingreso = (int) $data["SUMA"];
         $proyeccion = (int) $data["META"];
         $ref =  100 - $gap;

         if (($data["META"] == 0) and ($data["SUMA"] == 0)) { $gap = 0; $ref = 0; }
         if (($data["META"] <= $data["SUMA"]) and ($data["META"] <> 0) ) { $gap = 0; $ref = round( 100 * ($data["SUMA"] / $data["META"] ),2) ; }

         ($gap <> 0) ? $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'), new \OFC_Charts_Bar_Stack_Value($gap, '#1240AB'))) : $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'))) ;
         $agentes[] = $data["USUARIO"].chr(10).'Ingresos: MU$D '.number_format($ingreso,0,'.',',').chr(10).'Meta ingresos: MU$D '.number_format($proyeccion,0,'.',',').chr(10).'Gap: '.$gap.'%';
         $vals[] = $ref + $gap;
      }

      $bar->set_tooltip('#val#%');

      $y = new \OFC_Elements_Axis_Y();  
      $y->set_range( 0, max($vals) * 1.10 ,  max($vals) * 1.10 / 5 );

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($agentes);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/{key}/panel_imagen_graph.html", name="panel_imagen_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelVisitasGraphAction($anno,$mes,$key) {

      $key = $key."-".$anno."-".$mes.".png";

      $sql = "SELECT document_revision_id as id FROM documents WHERE document_name= '".$key."' AND template_type = 'panel' AND deleted = 0 LIMIT 1;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $img = 0;

      foreach($stmt->fetchAll() as $data) {
        $img = $data['id'].'.png';
      }
  
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:image.html.twig', array('img' => $img, 'key' => $key));
    }


    /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_backlog_data.json", name="panel_ingresos_por_market_manager_backlog_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerBacklogAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Backlog');

      $bar = new \OFC_Charts_Bar_Stack();

      $sql = "SELECT CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO,SUM(amount) as TOTAL, SUM(amount) - SUM( CASE 1 WHEN ( (DATEDIFF(CONCAT(".$anno.",'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(".$anno.",'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(".$anno.",'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(".$anno.",'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(".$anno.",'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA, (SELECT metas_metas_backlog_anno_por_agente.monto FROM metas_metas_backlog_anno_por_agente WHERE metas_metas_backlog_anno_por_agente.assigned_user_id = users.id AND metas_metas_backlog_anno_por_agente.anno = ".$anno." AND metas_metas_backlog_anno_por_agente.deleted = 0 LIMIT 1 ) as META FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won')  AND users.status = 'Active' GROUP BY opportunities.assigned_user_id ORDER BY USUARIO asc";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $agentes = array();
      $ingresos = array();
      $proyecciones = array();
      $vals = array(100);
 
      foreach($stmt->fetchAll() as $data) {
         
         if ($data["META"] <> 0) { 
           $gap = ($data["META"] > $data["SUMA"]) ? round( 100 * (($data["META"] - $data["SUMA"]) / $data["META"] ),2) : 0;
         } else { 
           $gap = 0;
         }

         $ingreso = (int) $data["SUMA"];
         $proyeccion = (int) $data["META"];
         $ref =  100 - $gap;

         if (($data["META"] == 0) and ($data["SUMA"] == 0)) { $gap = 0; $ref = 0; }
         if (($data["META"] <= $data["SUMA"]) and ($data["META"] <> 0) ) { $gap = 0; $ref = round( 100 * ($data["SUMA"] / $data["META"] ),2) ; }

         ($gap <> 0) ? $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'), new \OFC_Charts_Bar_Stack_Value($gap, '#1240AB'))) : $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'))) ;
         $agentes[] = $data["USUARIO"].chr(10).'Backlog: MU$D '.number_format($ingreso,0,'.',',').chr(10).'Meta backlog: MU$D '.number_format($proyeccion,0,'.',',').chr(10).'Gap: '.$gap.'%';
         $vals[] = $ref + $gap;
      }

      $bar->set_tooltip('#val#%');

      $y = new \OFC_Elements_Axis_Y();  
      $y->set_range( 0, max($vals) * 1.10 ,  max($vals) * 1.10 / 5 );

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($agentes);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

 /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_backlog_data_list.html", name="panel_ingresos_por_market_manager_backlog_data_list", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerBacklogDataListAction($anno,$mes) {
    
    
      $sql = "SELECT opportunities.name as OPORTUNIDAD, opportunities.date_closed as ADJUDICACION, opportunities_cstm.fecha_de_inicio_ejecucion_c as INICIO, opportunities_cstm.fecha_fin_contrato_c as FIN, CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO, amount as TOTAL, amount - ( CASE 1 WHEN ( (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > CONCAT(YEAR(date_closed),'-12-31'))) THEN ROUND((amount * (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF(CONCAT(YEAR(date_closed),'-12-31'),opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA, (SELECT metas_metas_backlog_anno_por_agente.monto FROM metas_metas_backlog_anno_por_agente WHERE metas_metas_backlog_anno_por_agente.assigned_user_id = users.id AND metas_metas_backlog_anno_por_agente.anno = YEAR(date_closed) AND metas_metas_backlog_anno_por_agente.deleted = 0 LIMIT 1 ) as META FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = ".$anno." ORDER BY USUARIO asc";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      return $this->render('PanelCrmBundle:Default:panel_ingresos_por_market_manager_backlog_data_list.html.twig', array('title' => 'Detalle ingresos '.$mes.'-'.$anno ,'results' => $stmt->fetchAll()));

    }


    /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_contratos_marco_data.json", name="panel_ingresos_por_market_manager_contratos_marco_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerContratosMarcoAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'Ingresos por contratos marco');

      $bar = new \OFC_Charts_Bar_Stack();

      $sql = "SELECT CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO, SUM( CASE 1 WHEN ( (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) > 1) AND (opportunities_cstm.fecha_fin_contrato_c > '".$anno."-12-31')) THEN ROUND((amount * (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c)/DATEDIFF(opportunities_cstm.fecha_fin_contrato_c,opportunities_cstm.fecha_de_inicio_ejecucion_c))),2)    WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_de_inicio_ejecucion_c) < 1) THEN 0  WHEN (DATEDIFF('".$anno."-12-31',opportunities_cstm.fecha_fin_contrato_c) > -1) THEN amount  END) as SUMA, (SELECT metas_metas_ingreso_anno_marco_por_agente.monto FROM metas_metas_ingreso_anno_marco_por_agente WHERE metas_metas_ingreso_anno_marco_por_agente.assigned_user_id = users.id AND metas_metas_ingreso_anno_marco_por_agente.anno = YEAR(date_closed) AND metas_metas_ingreso_anno_marco_por_agente.deleted = 0 LIMIT 1) as META  FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts_cstm ON accounts_cstm.id_c = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND sales_stage IN ('Closed Won') AND YEAR(date_closed) = ".$anno." AND opportunities_cstm.contrato_marzo_c = 1 GROUP BY opportunities.assigned_user_id ORDER BY USUARIO asc"; 

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $agentes = array();
      $ingresos = array();
      $proyecciones = array();
      $vals = array(100);
 
      foreach($stmt->fetchAll() as $data) {
         
         if ($data["META"] <> 0) { 
           $gap = ($data["META"] > $data["SUMA"]) ? round( 100 * (($data["META"] - $data["SUMA"]) / $data["META"] ),2) : 0;
         } else { 
           $gap = 0;
         }

         $ingreso = (int) $data["SUMA"];
         $proyeccion = (int) $data["META"];
         $ref =  100 - $gap;

         if (($data["META"] == 0) and ($data["SUMA"] == 0)) { $gap = 0; $ref = 0; }
         if (($data["META"] <= $data["SUMA"]) and ($data["META"] <> 0) ) { $gap = 0; $ref = round( 100 * ($data["SUMA"] / $data["META"] ),2) ; }

         ($gap <> 0) ? $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'),new \OFC_Charts_Bar_Stack_Value($gap, '#1240AB'))) : $bar->append_stack(array(new \OFC_Charts_Bar_Stack_Value($ref, '#ff0000'))) ;
         $agentes[] = $data["USUARIO"].chr(10).'Ingresos: MU$D '.number_format($ingreso,0,'.',',').chr(10).'Meta ingresos: MU$D '.number_format($proyeccion,0,'.',',').chr(10).'Gap: '.$gap.'%';
         $vals[] = $ref + $gap;
      }

      $bar->set_tooltip('#val#%');

      $y = new \OFC_Elements_Axis_Y();  
      $y->set_range( 0, max($vals) * 1.10 ,  max($vals) * 1.10 / 5 );

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array($agentes);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }


    /**
    * @Route("/{anno}/{mes}/panel_esfuerzo_cantidad_data.json", name="panel_esfuerzo_cantidad_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelEsfuerzoCantidadAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'ESFUERZO (Q) ' );

      $sql = "SELECT CASE sales_stage WHEN 'APROBADO' THEN 'Aprobado' WHEN 'Closed Lost' THEN 'Perdida' WHEN 'Closed Won' THEN 'Adjudicado' END as ITEM, count(*) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c WHERE  deleted = 0 AND sales_stage IN ('Closed Won','APROBADO','Closed Lost') AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage WITH ROLLUP;"; 
      
      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $info = $stmt->fetchAll();

      $total = 0;
      foreach($info as $data) {
        if ($data['ITEM'] == NULL) $total = $data['SUMA'];
      }

      foreach($info as $data) {
        if ($data['ITEM'] != NULL) { 
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower(substr($data["ITEM"],0,3))).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
        }
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(0);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_esfuerzo_precio_data.json", name="panel_esfuerzo_precio_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelEsfuerzoPrecioAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'ESFUERZO (P) ' );

      $sql = "SELECT CASE sales_stage WHEN 'APROBADO' THEN 'Aprobado' WHEN 'Closed Lost' THEN 'Perdida' WHEN 'Closed Won' THEN 'Adjudicado' END as ITEM, SUM(amount) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c WHERE  deleted = 0 AND sales_stage IN ('Closed Won','APROBADO','Closed Lost') AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage WITH ROLLUP;";

      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $info = $stmt->fetchAll();

      $total = 0;
      foreach($info as $data) {
        if ($data['ITEM'] == NULL) $total = $data['SUMA'];
      }

      foreach($info as $data) {
        if ($data['ITEM'] != NULL) { 
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower(substr($data["ITEM"],0,3))).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
        }
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(30);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }


    /**
    * @Route("/{anno}/{mes}/panel_hitrate_cantidad_data.json", name="panel_hitrate_cantidad_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelHitRateCantidadAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'HIT RATE (Q) ' );

      $sql = "SELECT CASE sales_stage WHEN 'Closed Lost' THEN 'Perdida' WHEN 'Closed Won' THEN 'Adjudicado' END as ITEM, count(*) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c WHERE  deleted = 0 AND sales_stage IN ('Closed Won','Closed Lost') AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage WITH ROLLUP;";

      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $info = $stmt->fetchAll();

      $total = 0;
      foreach($info as $data) {
        if ($data['ITEM'] == NULL) $total = $data['SUMA'];
      }

      foreach($info as $data) {
        if ($data['ITEM'] != NULL) { 
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower(substr($data["ITEM"],0,3))).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
        }
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(30);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_hitrate_precio_data.json", name="panel_hitrate_precio_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelHitRatePrecioAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'HIT RATE (P) ' );

      $sql = "SELECT CASE sales_stage WHEN 'Closed Lost' THEN 'Perdida' WHEN 'Closed Won' THEN 'Adjudicado' END as ITEM, SUM(amount) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c WHERE  deleted = 0 AND sales_stage IN ('Closed Won','Closed Lost') AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage WITH ROLLUP;";

      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $info = $stmt->fetchAll();

      $total = 0;
      foreach($info as $data) {
        if ($data['ITEM'] == NULL) $total = $data['SUMA'];
      }

      foreach($info as $data) {
        if ($data['ITEM'] != NULL) { 
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower(substr($data["ITEM"],0,3))).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
        }
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(60);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }


    /**
    * @Route("/{anno}/{mes}/proyeccion-nuevos-ingresos.html", name="panel_proyeccion_nuevos_ingresos_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosGraphAction($anno,$mes) {
    
      $div = 'panel_proyeccion_nuevos_ingresos_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 1015, 'height' => 200, 'key' => 'panel_proyeccion_nuevos_ingresos_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_lost_in_progress_graph.html", name="panel_lost_in_progress_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelLostinProgressGraphAction($anno,$mes) {
    
      $div = 'panel_lost_in_progress_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 1015, 'height' => 200, 'key' => 'panel_lost_in_progress_data','anno' => $anno,'mes' => $mes));
    }    


    /**
    * @Route("/{anno}/{mes}/proyeccion-backlog.html", name="panel_proyeccion_backlog_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionBacklogGraphAction($anno,$mes) {
    
      $div = 'panel_proyeccion_backlog_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 502, 'height' => 200, 'key' => 'panel_proyeccion_backlog_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/ingresos-total-anno.html", name="panel_ingresos_total_anno_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosTotalAnnoGraphAction($anno,$mes) {
    
      $div = 'panel_ingresos_total_anno_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 502, 'height' => 200, 'key' => 'panel_ingresos_total_anno_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/proyeccion-nuevos-ingresos_marco.html", name="panel_proyeccion_nuevos_ingresos_marco_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelProyeccionNuevosIngresosMarcoGraphAction($anno,$mes) {
    
      $div = 'panel_proyeccion_nuevos_ingresos_marco_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 502, 'height' => 200, 'key' => 'panel_proyeccion_nuevos_ingresos_marco_data','anno' => $anno,'mes' => $mes));
    } 

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_total.html", name="panel_ingresos_total_graph", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosTotalGraphAction($anno,$mes,$key,$title) {
    
      $div = 'panel_ingresos_total_graph_'.$key;
      return $this->render('PanelCrmBundle:Default:multi_graph.html.twig', array('div' => $div, 'width' => 345, 'height' => 160, 'route_key' => 'panel_ingresos_total_data','anno' => $anno,'mes' => $mes,'key' => $key,'title' => $title));
    }

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_distribucion_bu_graph.html", name="panel_ingresos_distribucion_bu_graph", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosDistribucionBuGraphGraphAction($anno,$mes,$key,$title) {
    
      $div = 'panel_ingresos_distribucion_bu_graph'.$key;
      return $this->render('PanelCrmBundle:Default:multi_graph.html.twig', array('div' => $div, 'width' => 705, 'height' => 160, 'route_key' => 'panel_ingresos_distribucion_bu_data','anno' => $anno,'mes' => $mes,'key' => $key,'title' => $title));
    }

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos.html", name="panel_ingresos_graph", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosGraphAction($anno,$mes,$key,$title) {
    
      $div = 'panel_ingresos_graph_'.$key;
      return $this->render('PanelCrmBundle:Default:multi_graph.html.twig', array('div' => $div, 'width' => 705, 'height' => 160, 'route_key'=> 'panel_ingresos_data','anno' => $anno,'mes' => $mes,'key' => $key,'title' => $title));
    }

    /**
    * @Route("/{anno}/{mes}/{key}/{title}/panel_ingresos_por_tipo_de_servicio.html", name="panel_ingresos_por_tipo_de_servicio_graph", defaults={"anno"="2013","mes"="01","key"="UE","title"="TOTAL UE"}, options={"expose"=true})
    */
    public function panelIngresosPorTipoDeServicioGraphAction($anno,$mes,$key,$title) {

      $div = 'panel_ingresos_por_tipo_de_servicio_graph_'.$key;
      return $this->render('PanelCrmBundle:Default:multi_graph.html.twig', array('div' => $div, 'width' => 350, 'height' => 200, 'route_key' => 'panel_ingresos_por_tipo_de_servicio_data','anno' => $anno, 'mes' => $mes,'key' => $key,'title' => $title));
    }
  
    /**
    * @Route("/panel_ingresos_por_tipo_de_servicio_distribucion.html", name="panel_ingresos_por_tipo_de_servicio_distribucion_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorTipoDeServicioDistribucionGraphAction($anno,$mes) {
    
      $div = 'panel_ingresos_por_tipo_de_servicio_distribucion_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 300, 'height' => 200, 'key' => 'panel_ingresos_por_tipo_de_servicio_distribucion_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_nuevas_oportunidades_graph.html", name="panel_ingresos_por_market_manager_nuevas_oportunidades_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerNuevasOportunidadesGraphAction($anno,$mes) {
    
      $div = 'panel_ingresos_por_market_manager_nuevas_oportunidades_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 1024, 'height' => 200, 'key' => 'panel_ingresos_por_market_manager_nuevas_oportunidades_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/panel_ingresos_por_market_manager_backlog_graph.html", name="panel_ingresos_por_market_manager_backlog_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerBacklogGraphAction($anno,$mes) {
    
      $div = 'panel_ingresos_por_market_manager_backlog_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 1024, 'height' => 200, 'key' => 'panel_ingresos_por_market_manager_backlog_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_ingresos_por_market_manager_contratos_marco_graph.html", name="panel_ingresos_por_market_manager_contratos_marco_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelIngresosPorMarketManagerContratosMarcoGraphAction($anno,$mes) {
    
      $div = 'panel_ingresos_por_market_manager_contratos_marco_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 1024, 'height' => 200, 'key' => 'panel_ingresos_por_market_manager_contratos_marco_data','anno' => $anno,'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_selectividad_etapas_graph.html", name="panel_selectividad_etapas_graph", options={"expose"=true})
    */
    public function panelSelectividadEtapasGraphAction($anno,$mes) {

      $sql = "SELECT CASE sales_stage WHEN 'Closed Lost' THEN 'PERDIDA' WHEN 'Closed Won' THEN 'ADJUDICADA' ELSE sales_stage END as STATUS, count(*) as CANTIDAD, SUM(amount) AS MONTO FROM opportunities WHERE deleted = 0 AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage ORDER BY sales_stage ASC;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $items = array();

      $items['PERDIDAS'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['ADJUDICADA'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['DESIERTA'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['APROBADO'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['ANULADA'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['EN_ESTUDIO'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['RECHAZADA'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['DESCARTADA'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['PROSPECCION_CONTINGENTE'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      $items['PROSPECCION_GENERAL'] = array('CANTIDAD' => 0, 'SUMA' => 0);
      
      foreach ($stmt->fetchAll() as $data) {
        switch($data["STATUS"]) {
          case 'PERDIDA'   : $items['PERDIDAS'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'ADJUDICADA': $items['ADJUDICADA'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'DESIERTA'  : $items['DESIERTA'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'APROBADO'  : $items['APROBADO'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'ANULADA'   : $items['ANULADA'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'EN_ESTUDIO'  : $items['EN_ESTUDIO'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'RECHAZADA'  : $items['RECHAZADA'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'DESCARTADA'  : $items['DESCARTADA'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'PROSPECCION_CONTINGENTE'  : $items['PROSPECCION_CONTINGENTE'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
          case 'PROSPECCION_GENERAL'  : $items['PROSPECCION_GENERAL'] = array('CANTIDAD' => $data['CANTIDAD'], 'SUMA' => $data['MONTO']);
            break;
        }
      }  

      $items['ENTREGADAS'] = array('CANTIDAD' => $items['APROBADO']['CANTIDAD'] + $items['DESIERTA']['CANTIDAD'] + $items['ADJUDICADA']['CANTIDAD'] + $items['PERDIDAS']['CANTIDAD'], 'SUMA' => $items['APROBADO']['SUMA'] + $items['DESIERTA']['SUMA'] + $items['ADJUDICADA']['SUMA'] + $items['PERDIDAS']['SUMA']);

      $items['PASA_A_ESTUDIO'] = array('CANTIDAD' => $items['ENTREGADAS']['CANTIDAD'] + $items['EN_ESTUDIO']['CANTIDAD'] + $items['ANULADA']['CANTIDAD'], 'SUMA' => $items['ENTREGADAS']['SUMA'] + $items['EN_ESTUDIO']['SUMA'] + $items['ANULADA']['SUMA']);

      $items['PASA_A_SELECTIVIDAD'] = array('CANTIDAD' => $items['RECHAZADA']['CANTIDAD'] + $items['PASA_A_ESTUDIO']['CANTIDAD'], 'SUMA' => $items['RECHAZADA']['SUMA'] + $items['EN_ESTUDIO']['SUMA'] + $items['PASA_A_ESTUDIO']['SUMA']);
   
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:selectividad_etapas.html.twig', array('data' => $items));
    }

    /**
    * @Route("/{anno}/{mes}/panel_causas_perdidas_graph.html", name="panel_selectividad_causas_perdidas_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelCausasPerdidasGraphAction($anno,$mes) {

     $sql = "SELECT IFNULL(rechazo_por_precio_c,0) as PRECIO,IFNULL(rechazo_por_retraso_c,0) as  RETRASO,IFNULL(rechazo_por_tiempo_de_ejecu_c,0) as TIEMPO_DE_EJECUCION,IFNULL(rechazo_por_calif_tecnica_c,0) as CALIFICACION_TECNICA,IFNULL(rechazo_otros_c,0) as OTROS, amount as MONTO FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE deleted = 0 AND sales_stage IN ('Closed Lost') AND YEAR(date_closed) = '".$anno."';"; 

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $perdidas = array('PRECIO' => array('HITS' => 0,'MONTO' =>0),'RETRASO' => array('HITS' => 0,'MONTO' =>0),'TIEMPO_DE_EJECUCION' => array('HITS' => 0,'MONTO' =>0),'CALIFICACION_TECNICA' => array('HITS' => 0,'MONTO' =>0),'OTROS' => array('HITS' => 0,'MONTO' =>0));

      $hits = 0;
      $total = 0;
      $info = $stmt->fetchAll();
      foreach ($info as $data) {

        if ($data['PRECIO'] == 1) { 
          $perdidas['PRECIO']['HITS'] = $perdidas['PRECIO']['HITS'] + 1;
          $perdidas['PRECIO']['MONTO'] = $perdidas['PRECIO']['MONTO'] + $data['MONTO'];
        }

        if ($data['RETRASO'] == 1) { 
          $perdidas['RETRASO']['HITS'] = $perdidas['RETRASO']['HITS'] + 1;
          $perdidas['RETRASO']['MONTO'] = $perdidas['RETRASO']['MONTO'] + $data['MONTO'];
        }

        if ($data['TIEMPO_DE_EJECUCION'] == 1) { 
          $perdidas['TIEMPO_DE_EJECUCION']['HITS'] = $perdidas['TIEMPO_DE_EJECUCION']['HITS'] + 1;
          $perdidas['TIEMPO_DE_EJECUCION']['MONTO'] = $perdidas['TIEMPO_DE_EJECUCION']['MONTO'] + $data['MONTO'];
        }

        if ($data['CALIFICACION_TECNICA'] == 1) { 
          $perdidas['CALIFICACION_TECNICA']['HITS'] = $perdidas['CALIFICACION_TECNICA']['HITS'] + 1;
          $perdidas['CALIFICACION_TECNICA']['MONTO'] = $perdidas['CALIFICACION_TECNICA']['MONTO'] + $data['MONTO'];
        }

        if ($data['OTROS'] == 1) { 
          $perdidas['OTROS']['HITS'] = $perdidas['OTROS']['HITS'] + 1;
          $perdidas['OTROS']['MONTO'] = $perdidas['OTROS']['MONTO'] + $data['MONTO'];
        }
        $total = $total + $data['MONTO'];
        $hits++;
      }
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:selectividad_causas_perdidas.html.twig', array('perdidas' => $perdidas,'hits' => $hits, 'total' => $total));
    }

    /**
    * @Route("/{anno}/{mes}/panel_esfuerzo_cantidad_graph.html", name="panel_esfuerzo_cantidad_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelEsfuerzoCantidadGraphAction($anno,$mes) {
    
      $div = 'panel_esfuerzo_cantidad_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 245, 'height' => 160, 'key' => 'panel_esfuerzo_cantidad_data', 'anno' => $anno, 'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_esfuerzo_precio_graph.html", name="panel_esfuerzo_precio_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelEsfuerzoPrecioGraphAction($anno,$mes) {
    
      $div = 'panel_esfuerzo_precio_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 245, 'height' => 160, 'key' => 'panel_esfuerzo_precio_data', 'anno' => $anno, 'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_esfuerzo_status_graph.html", name="panel_esfuerzo_status_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelEsfuerzoStatusGraphAction($anno,$mes) {

      $sql = "SELECT CASE sales_stage WHEN 'Closed Lost' THEN 'PERDIDA' WHEN 'Closed Won' THEN 'ADJUDICADA' ELSE sales_stage END as STATUS, count(*) as CANTIDAD, SUM(amount) AS MONTO FROM opportunities WHERE deleted = 0 AND sales_stage IN ('APROBADO','Closed Won','Closed Lost') AND YEAR(date_closed) = '".$anno."' GROUP BY sales_stage ORDER BY sales_stage ASC";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
   
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:esfuerzo_status.html.twig', array('results' => $stmt->fetchAll()));
    }

    /**
    * @Route("/{anno}/{mes}/panel_hitrate_cantidad_graph.html", name="panel_hitrate_cantidad_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelHitRateCantidadGraphAction($anno,$mes) {
    
      $div = 'panel_hitrate_cantidad_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 245, 'height' => 160, 'key' => 'panel_hitrate_cantidad_data', 'anno' => $anno, 'mes' => $mes));
    }

    /**
    * @Route("/{anno}/{mes}/panel_hitrate_precio_graph.html", name="panel_hitrate_precio_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelHitRatePrecioGraphAction($anno,$mes) {
    
      $div = 'panel_hitrate_precio_graph';
      return $this->render('PanelCrmBundle:Default:graph.html.twig', array('div' => $div, 'width' => 245, 'height' => 160, 'key' => 'panel_hitrate_precio_data', 'anno' => $anno, 'mes' => $mes));
    }


    /**
    * @Route("/{anno}/{mes}/panel_top_prospeccion_graph.html", name="panel_top_prospeccion_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelTopProspeccionGraphAction($anno,$mes) {
    
      $sql = " SELECT opportunities.name as NOMBRE, amount as MONTO, fecha_de_entrega_c as FECHA, accounts.name as CLIENTE, CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO, opportunities.description as OBSERVACIONES FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts ON accounts.id = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND YEAR(date_closed) = '".$anno."' AND sales_stage IN ('PROSPECCION_CONTINGENTE','PROSPECCION_GENERAL') GROUP BY opportunities.id ORDER BY amount DESC LIMIT 7";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();

      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:top_prospeccion.html.twig', array('results' => $stmt->fetchAll(),'title' => 'prospección'));
    }

    /**
    * @Route("/{anno}/{mes}/panel_top_en_espera_graph.html", name="panel_top_en_espera_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelTopEnEsperaGraphAction($anno,$mes) {

     $sql = " SELECT opportunities.name as NOMBRE, amount as MONTO, fecha_de_entrega_c as FECHA, accounts.name as CLIENTE, CONCAT(SUBSTRING(users.first_name,1,1),'. ', SUBSTRING(users.last_name,1,  LOCATE(' ',users.last_name) - 1 )) as USUARIO, opportunities.description as OBSERVACIONES FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts ON accounts.id = accounts_opportunities.account_id INNER JOIN users ON users.id = opportunities.assigned_user_id WHERE opportunities.deleted = 0 AND accounts_opportunities.deleted = 0 AND YEAR(date_closed) = '".$anno."' AND sales_stage IN ('APROBADO') GROUP BY opportunities.id ORDER BY amount DESC LIMIT 7";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:top_prospeccion.html.twig', array('results' => $stmt->fetchAll(),'title' => 'espera de respuesta'));
    }

    /**
    * @Route("/{anno}/{mes}/panel_work_in_progress_graph.html", name="panel_work_in_progress_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelWorkInProgressGraphAction($anno,$mes) {

     $sql = "SELECT bu_c as BU, accounts_cstm.segmento_c AS MERCADO, (CASE sales_stage WHEN 'Closed Won' THEN 'ADJUDICADO' WHEN 'Closed Lost' THEN 'PERDIDA' ELSE sales_stage END) as STATUS,  opportunities.name AS NOMBRE, accounts.name AS CLIENTE,codigo_oportunidad_c AS NUMERO_DE_OPERACION,YEAR(date_closed) as ANNO_FECHA_ADJUDICACION,date_closed as FECHA_ADJUDICACION,amount AS MONTO_MUSD, CONCAT(margen_esperado_c,'%') as MARGEN_ESPERADO,CONCAT(probabiidad_adjudicacion_c,'%') as PROBABILIDAD_DE_ADJUDICACION,ROUND((CASE 1 WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) IS NULL) THEN NULL WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) < 366) THEN 100 ELSE ROUND(((DATEDIFF(CONCAT(YEAR(fecha_de_inicio_ejecucion_c),'-12-31'), fecha_de_inicio_ejecucion_c) / 365) * 100 ) / (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) / 365) ,0) END) * (amount / 100) * probabiidad_adjudicacion_c / 100 ,2) AS INGRESO_PROBABILIDAD_MARGEN_ANNO_DE_INICIO_CONTRATO,CASE 1 WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) IS NULL) THEN NULL WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) < 366) THEN '100%' ELSE CONCAT(ROUND(((DATEDIFF(CONCAT(YEAR(fecha_de_inicio_ejecucion_c),'-12-31'), fecha_de_inicio_ejecucion_c) / 365) * 100) / (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) / 365) ,0),'%') END AS FACTURACION_ANNO_DE_INICIO_CONTRATO,ROUND((CASE 1 WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) IS NULL) THEN NULL WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) < 366) THEN 100 ELSE ROUND(((DATEDIFF(CONCAT(YEAR(fecha_de_inicio_ejecucion_c),'-12-31'), fecha_de_inicio_ejecucion_c) / 365) * 100 ) / (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) / 365) ,0) END) * (amount / 100) ,2) AS INGRESOS_ANNO_DE_INICIO_CONTRATO, ROUND((CASE 1 WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) IS NULL) THEN NULL WHEN (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) < 366) THEN 100 ELSE ROUND(((DATEDIFF(CONCAT(YEAR(fecha_de_inicio_ejecucion_c),'-12-31'), fecha_de_inicio_ejecucion_c) / 365) * 100) / (DATEDIFF(fecha_fin_contrato_c, fecha_de_inicio_ejecucion_c) / 365) ,0) END)  * (amount / 100) *  margen_esperado_c / 100 ,2) AS MARGEN_ANNO_DE_INICIO_CONTRATO,CONCAT('http://crm.cam-la.com/index.php?module=Opportunities&amp;action=DetailView&amp;record=',opportunities.id) as VER_DETALLE FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c INNER JOIN accounts_opportunities ON accounts_opportunities.opportunity_id = opportunities.id INNER JOIN accounts ON accounts.id = accounts_opportunities.account_id INNER JOIN accounts_cstm ON accounts.id = accounts_cstm.id_c WHERE opportunities.deleted = 0 AND sales_stage IN ('PROSPECCION_CONTINGENTE','Closed Won','APROBADO','EN_ESTUDIO') AND YEAR(date_closed) = ".$anno." GROUP BY opportunities.id ORDER BY sales_stage, date_closed ASC;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:work_in_progress.html.twig', array('results' => $stmt->fetchAll(),'title' => 'espera de respuesta'));
    }   

    /**
    * @Route("/{anno}/{mes}/panel_visitas_clientes_graph.html", name="panel_visitas_clientes_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelVisitasClientesGraphAction($anno,$mes) {

      $sql = "SELECT accounts.id as account_id, accounts.name as account, (SELECT count(*) FROM meetings WHERE parent_type = 'Accounts' AND parent_id = accounts.id AND meetings.deleted = 0 AND YEAR(meetings.date_start) = ".$anno." ) as meetings, (SELECT count(*) FROM meetings m INNER JOIN accounts_opportunities ON m.parent_id = accounts_opportunities.opportunity_id  WHERE parent_type = 'Opportunities' AND accounts_opportunities.account_id = accounts.id AND m.deleted = 0 AND YEAR(m.date_start) = ".$anno." ) as meetings_opportunities FROM accounts WHERE accounts.deleted = 0 HAVING (meetings + meetings_opportunities) > 0 ORDER BY (meetings + meetings_opportunities ) DESC, account ASC";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:meetings_in_progress.html.twig', array('results' => $stmt->fetchAll(),'title' => 'Reuniones', 'anno' => $anno));
    }   

   /**
    * @Route("/{anno}/{mes}/{account_id}/panel_detalle_meetings_por_agente.html", name="panel_detalle_meetings_por_agente", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleMeetingsPorAgenteAction($anno,$mes,$account_id) {

      $sql = "SELECT CONCAT(users.user_name,' ',users.first_name,' ', users.last_name) as owner, (SELECT count(*) FROM meetings m WHERE parent_type = 'Accounts' AND parent_id = '".$account_id."' AND m.deleted = 0 AND YEAR(m.date_start) = 2015 AND m.assigned_user_id = meetings.assigned_user_id ) as meetings,(SELECT count(*) FROM meetings m2 INNER JOIN accounts_opportunities ON m2.parent_id = accounts_opportunities.opportunity_id  WHERE parent_type = 'Opportunities' AND accounts_opportunities.account_id = '".$account_id."' AND m2.deleted = 0 AND YEAR(m2.date_start) = ".$anno." ) as meetings_opportunities FROM meetings INNER JOIN users ON users.id = meetings.assigned_user_id AND meetings.deleted = 0 GROUP BY assigned_user_id HAVING (meetings + meetings_opportunities) > 0;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:details_meetings_in_progress.html.twig', array('results' => $stmt->fetchAll(),'title' => 'Reuniones', 'anno' => $anno));
    }        

    /**
    * @Route("/{anno}/{mes}/panel_discovery_clientes_graph.html", name="panel_discovery_clientes_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDiscoveryClientesGraphAction($anno,$mes) {

      $sql = "SELECT CONCAT(users.user_name,' ',users.first_name,' ', users.last_name) as owner, meetings.assigned_user_id, (SELECT count(*) FROM meetings m WHERE parent_type = 'Accounts' AND m.deleted = 0 AND YEAR(m.date_start) = 2015 AND m.assigned_user_id = meetings.assigned_user_id ) as meetings,(SELECT count(*) FROM meetings m2 INNER JOIN accounts_opportunities ON m2.parent_id = accounts_opportunities.opportunity_id  WHERE parent_type = 'Opportunities' AND 1 AND m2.deleted = 0 AND YEAR(m2.date_start) = ".$anno." ) as meetings_opportunities FROM meetings INNER JOIN users ON users.id = meetings.assigned_user_id AND meetings.deleted = 0 GROUP BY assigned_user_id HAVING (meetings + meetings_opportunities) > 0;";

      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
    
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:meetings_discovery_in_progress.html.twig', array('results' => $stmt->fetchAll(),'title' => 'Reuniones', 'anno' => $anno));
    }     

    /**
    * @Route("/{anno}/{mes}/{assigned_user_id}/panel_detalle_discovery_por_agente.html", name="panel_detalle_discovery_por_agente", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleDiscoveryPorAgenteAction($anno,$mes,$assigned_user_id) {

      
      $div = 'graph_'.rand();
      return $this->render('PanelCrmBundle:Default:details_discovery_in_progress.html.twig', array('title' => 'Oportunidades', 'anno' => $anno, 'assigned_user_id' => $assigned_user_id));
    }    

    /**
    * @Route("/{anno}/{mes}/{assigned_user_id}/{width}/panel_detalle_discovery_in_progress_graph.html", name="panel_detalle_discovery_in_progress_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleDiscoveryPorAgenteGraphAction($anno,$mes,$assigned_user_id, $width) {
    
      $div = 'panel_grafico_discovery_show_'.$assigned_user_id;
      return $this->render('PanelCrmBundle:Default:panel_detalle_discovery_in_progress_graph.html.twig', array('div' => $div, 'width' => $width, 'height' => 260, 'key' => 'panel_detalle_discovery_in_progress_data', 'anno' => $anno, 'mes' => $mes, 'assigned_user_id' => $assigned_user_id, 'width' => $width));
    }

    /**
    * @Route("/{anno}/{mes}/{assigned_user_id}/{width}/panel_detalle_discovery_in_progress_data.json", name="panel_detalle_discovery_in_progress_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleDiscoveryPorAgenteDataAction($anno,$mes,$assigned_user_id, $width) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'STATUS DE LAS OPORTUNIDADES DEL AGENTE ' );

      $sql = "SELECT CASE sales_stage WHEN 'APROBADO' THEN 'Aprobado' WHEN 'Closed Lost' THEN 'Perdida' WHEN 'Closed Won' THEN 'Adjudicado' ELSE sales_stage END as ITEM, count(*) as SUMA FROM opportunities INNER JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c WHERE opportunities.deleted = 0 AND sales_stage IN ('Closed Won','APROBADO','Closed Lost','ANULADA','DESCARTADA') AND YEAR(date_closed) = '".$anno."' AND assigned_user_id = '".$assigned_user_id."' GROUP BY sales_stage WITH ROLLUP;"; 
      
      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $info = $stmt->fetchAll();

      $total = 0;
      foreach($info as $data) {
        if ($data['ITEM'] == NULL) $total = $data['SUMA'];
      }

      foreach($info as $data) {
        if ($data['ITEM'] != NULL) { 
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower(substr($data["ITEM"],0,3))).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
        }
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(0);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }

    /**
    * @Route("/{anno}/{mes}/panel_lost_in_progress_data.json", name="panel_lost_in_progress_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelLostInProgressDataAction($anno,$mes) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( ' ' );

      $sql = "SELECT rechazo_otros_c , rechazo_por_calif_tecnica_c , rechazo_por_precio_c, rechazo_por_retraso_c , rechazo_por_tiempo_de_ejecu_c FROM opportunities INNER JOIN opportunities_cstm ON opportunities_cstm.id_c = opportunities.id WHERE opportunities.sales_stage = 'Closed Lost' AND YEAR(date_closed) = ".$anno." AND deleted = 0;"; 
      
      $dataPie = array();
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $items = $stmt->fetchAll();

      $total = 0;
      $info = array('rechazo_sin_especificar_c' => array('SUMA' => 0, 'ITEM' => 'SIN ESPECIFICAR') ,'rechazo_otros_c' => array('SUMA' => 0, 'ITEM' => 'OTRAS' ), 'rechazo_por_calif_tecnica_c' => array('SUMA' => 0, 'ITEM' => 'CALIFICACIÓN TÉCNICA')  , 'rechazo_por_precio_c' => array('SUMA' => 0, 'ITEM' => 'PRECIO') , 'rechazo_por_retraso_c' => array('SUMA' => 0, 'ITEM' => 'RETRASO') , 'rechazo_por_tiempo_de_ejecu_c' => array('SUMA' => 0 ,'ITEM' => 'TIEMPO DE EJECUCIÓN') );

      foreach($items as $data) {
        //SI NO SE ESPECIFICA ES "SIN ESPECIFICAR"
        if ( ( (int) $data['rechazo_por_calif_tecnica_c'] + (int) $data['rechazo_por_retraso_c'] + (int) $data['rechazo_por_tiempo_de_ejecu_c'] + (int) $data['rechazo_por_precio_c'] + (int) $data['rechazo_otros_c'] ) == 0) {
          $info['rechazo_sin_especificar_c']['SUMA'] = $info['rechazo_sin_especificar_c']['SUMA'] + 1;
        }

        $info['rechazo_otros_c']['SUMA'] =  $info['rechazo_otros_c']['SUMA'] + (int) $data['rechazo_otros_c'];

        $info['rechazo_por_calif_tecnica_c']['SUMA'] =  $info['rechazo_por_calif_tecnica_c']['SUMA'] + (int) $data['rechazo_por_calif_tecnica_c'];
        
        $info['rechazo_por_precio_c']['SUMA'] =  $info['rechazo_por_precio_c']['SUMA'] + (int) $data['rechazo_por_precio_c'];

        $info['rechazo_por_retraso_c']['SUMA'] =  $info['rechazo_por_retraso_c']['SUMA'] + (int) $data['rechazo_por_retraso_c'];        

        $info['rechazo_por_tiempo_de_ejecu_c']['SUMA'] =  $info['rechazo_por_tiempo_de_ejecu_c']['SUMA'] + (int) $data['rechazo_por_tiempo_de_ejecu_c'];

      }

      foreach ($info as $data) {
        $total = $total + $data['SUMA'];
      }   

      foreach($info as $data) {
          $dataPie[] = new \OFC_Charts_Pie_Value(round(100 * (int)$data["SUMA"] / $total,0), ucfirst(strtolower($data["ITEM"])).' ('.round(100 * (int)$data["SUMA"] / $total,0).'%)'); 
      }

      $bar = new \OFC_Charts_Pie();
      $bar->set_start_angle(0);
      $bar->values = array_values($dataPie);

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }



    /**
    * @Route("/{anno}/{mes}/{assigned_user_id}/{width}/panel_detalle_discovery_metas_in_progress_graph.html", name="panel_detalle_discovery_metas_in_progress_graph", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleDiscoveryMetasPorAgenteGraphAction($anno,$mes,$assigned_user_id, $width) {
    
      $div = 'panel_grafico_discovery_metas_show_'.$assigned_user_id;
      return $this->render('PanelCrmBundle:Default:panel_detalle_discovery_in_progress_graph.html.twig', array('div' => $div, 'width' => $width, 'height' => 260, 'key' => 'panel_detalle_discovery_metas_in_progress_data', 'anno' => $anno, 'mes' => $mes, 'assigned_user_id' => $assigned_user_id, 'width' => $width));
    }

    /**
    * @Route("/{anno}/{mes}/{assigned_user_id}/{width}/panel_detalle_discovery_metas_in_progress_data.json", name="panel_detalle_discovery_metas_in_progress_data", defaults={"anno"="2013","mes"="01"}, options={"expose"=true})
    */
    public function panelDetalleDiscoveryMetasPorAgenteDataAction($anno,$mes,$assigned_user_id, $width) {
    
      include_once(__dir__."/../Util/OFC/OFC_Chart.php");
      $title = new \OFC_Elements_Title( 'STATUS DE LAS REUNIONES V/S METAS DEL AGENTE ' );

      $bar = new \OFC_Charts_Bar_Stack();

      $sql = "SELECT CONCAT(users.user_name,' ',users.first_name,' ', users.last_name) as owner, meetings.assigned_user_id, (SELECT count(*) FROM meetings m WHERE parent_type = 'Accounts' AND m.deleted = 0 AND YEAR(m.date_start) = 2015 AND m.assigned_user_id = meetings.assigned_user_id ) as meetings,(SELECT count(*) FROM meetings m2 INNER JOIN accounts_opportunities ON m2.parent_id = accounts_opportunities.opportunity_id  WHERE parent_type = 'Opportunities' AND 1 AND m2.deleted = 0 AND YEAR(m2.date_start) = ".$anno." ) as meetings_opportunities FROM meetings INNER JOIN users ON users.id = meetings.assigned_user_id AND meetings.deleted = 0 AND meetings.assigned_user_id = '".$assigned_user_id."' GROUP BY assigned_user_id HAVING (meetings + meetings_opportunities) > 0;";    
  
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      //Valores
      $valor = 0;
      foreach($stmt->fetchAll() as $data) {
        $valor = (int) $data['meetings'] + (int) $data['meetings_opportunities'];
        $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value((int) $data['meetings'], '#ff0000'),  new \OFC_Charts_Bar_Stack_Value((int) $data['meetings_opportunities'], '#1240AB')) ); 
      }

      //Metas
      $sql = "SELECT sem_1_c, sem_2_c FROM cammr_metasreuniones INNER JOIN cammr_metasreuniones_cstm ON cammr_metasreuniones_cstm.id_c = cammr_metasreuniones.id WHERE deleted = 0 AND anno_c = '".$anno."' AND user_id_c = '".$assigned_user_id."';";         
  
      $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($sql);
      $stmt->execute();      

      $meta = 0;
      foreach($stmt->fetchAll() as $data) {       
        $meta = $data['sem_1_c'] + $data['sem_2_c'];
        $bar->append_stack( array( new \OFC_Charts_Bar_Stack_Value((int) $data['sem_1_c'], '#ff0000'),  new \OFC_Charts_Bar_Stack_Value((int) $data['sem_2_c'], '#1240AB')) ); 
      }

      $y = new \OFC_Elements_Axis_Y();
      $max = round(max($valor,$meta) * 1.15,0);
      $y->set_range( 0, $max, round($max,-3)); 

      $x = new \OFC_Elements_Axis_X();
      $x->set_labels_from_array( array('Reuniones','Metas'));

      $chart = new \OFC_Chart();
      $chart->set_bg_colour( '#FFFFFF' );
      $chart->set_title( $title );
      $chart->add_element( $bar );
      $chart->set_x_axis( $x );
      $chart->add_y_axis( $y );

      $response = new Response($chart->toPrettyString());
      $response->headers->set('Content-Type', 'application/json');

      return $response;
      
    }


}
