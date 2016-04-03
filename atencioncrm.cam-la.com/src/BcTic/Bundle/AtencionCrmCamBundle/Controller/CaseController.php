<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError as FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase;
use BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCaseSearch;
use BcTic\Bundle\AtencionCrmCamBundle\Form\CustomerCaseType;
use BcTic\Bundle\AtencionCrmCamBundle\Form\CustomerCaseSearchType;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class CaseController extends Controller
{
    /**
     * @Route("/" ,name="default_index")
     * @Template()
     */
    public function addAction()
    {

        $entity = new CustomerCase();
        $entity->setType("CONSULTA_CLIENTE");
        $entity->setStatus("New");
        $form = $this->createCreateForm($entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new Case entity.
     *
     * @Route("/add", name="case_create")
     * @Method("POST")
     * @Template("BcTicAtencionCrmCamBundle:Case:add.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new CustomerCase();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            

            $entity->upload();
            
            //Persisto el objeto como Json
            $normalizer = new GetSetMethodNormalizer();
            //$normalizer->setIgnoredAttributes(array('age'));
            $encoder = new JsonEncoder();

            $serializer = new Serializer(array($normalizer), array($encoder));

            //Guardo el objecto en data:
            $file = $this->container->get('kernel')->getRootDir().'/Resources/data/case-'.date('U').'-data.json';
            file_put_contents($file, $serializer->serialize($entity, 'json'));

            $this->get('session')->getFlashBag()->add(
              'notice',
              'Su consulta fue enviada a nuestros sistema de atención de clientes CAM, le contactaremos a la brevedad.'
            );

            return $this->redirect($this->generateUrl('case_added'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

   
    protected function createCreateForm(CustomerCase $entity) 
    {
        $form = $this->createForm(new CustomerCaseType(), $entity, array(
            'action' => $this->generateUrl('case_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Guardar'));
        return $form;
    }

    protected function createSearchForm(CustomerCaseSearch $entity) 
    {

    $form = $this->createForm(new CustomerCaseSearchType(), $entity, array(
            'action' => $this->generateUrl('case_search'),
            'method' => 'POST',
        ));

        $form->add('search','submit', array('label' => 'Buscar'));
        return $form;
    }        

    /**
     * @Route("/casos/ok" ,name="case_added")
     * @Template()
     */
    public function addedAction()
    {
        return array();
    }

    /**
     * @Route("/casos" ,name="case_index")
     * @Template()
     */
    public function indexAction()
    {
        $entity = new CustomerCaseSearch();
        $entity->setTicket("");
        $form = $this->createSearchForm($entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );

    }

    /**
     * @Route("/casos/show.html" ,name="case_show")
     * @Method("POST")
     * @Template()
     */
    public function showAction($ticket) {

      $file = $this->container->get('kernel')->getRootDir().'/Resources/data/server/case-'.$ticket.'-data.json';
      $normalizer = new GetSetMethodNormalizer();
      $encoder = new JsonEncoder();
      $serializer = new Serializer(array($normalizer), array($encoder));
      $content = file_get_contents($file);
      $data = json_decode(json_encode($content),true);

      $entity = $serializer->deserialize($data, 'BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase','json');

      return array('entity' => $entity);
    }

    /**
     * @Route("/casos/search" ,name="case_search")
     * @Method("POST")
     * @Template("BcTicAtencionCrmCamBundle:Case:index.html.twig")
     */
    public function searchAction(Request $request)
    {
        $entity = new CustomerCaseSearch();
        $form = $this->createSearchForm($entity);
        $form->handleRequest($request);

        $file = $this->container->get('kernel')->getRootDir().'/Resources/data/server/case-'.$entity->getTicket().'-data.json';

        if ($form->isValid()) {

          try {

            if (!is_readable($file)) throw new \Exception("No fue posible encontrar un caso asociado a este número y el email indicado.");

            //Persisto el objeto como Json
            $normalizer = new GetSetMethodNormalizer();
            //$normalizer->setIgnoredAttributes(array('age'));
            $encoder = new JsonEncoder();

            $serializer = new Serializer(array($normalizer), array($encoder));

            $content = file_get_contents($file);
            $data = json_decode(json_encode($content),true);

            $obj = $serializer->deserialize($data, 'BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase','json');

            if ($obj->getEmail() <> $entity->getEmail()) throw new \Exception("No fue posible encontrar un caso asociado a este número y el email indicado.");
            
            return $this->forward('BcTicAtencionCrmCamBundle:Case:show', array('ticket' => $entity->getTicket()));
          } catch (\Exception $e) {
              $this->get('session')->getFlashBag()->add(
                'error',
                $e->getMessage()
              );
              return array(
                'entity' => $entity,
                'form'   => $form->createView(),
              );
          }
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }
}
