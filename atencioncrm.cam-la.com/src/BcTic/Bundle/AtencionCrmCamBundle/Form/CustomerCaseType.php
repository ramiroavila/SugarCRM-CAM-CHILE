<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerCaseType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',array('label' => 'Nombre'))
            ->add('email','email',array('label' => 'Correo electrónico'))
            ->add('customerPhone','text',array('label' => 'Fono'))
            ->add('company','text', array('label' => 'Empresa'))
            ->add('companyRole','text', array('label' => 'Cargo'))
            ->add('companyArea','text', array('label' => 'Área'))
            ->add('ticket','hidden')
            ->add('type','hidden')
            ->add('description','textarea', array('label' => 'Solicitud/Consulta', 'max_length' => 250))
            ->add('status','hidden')
            ->add('file', 'file', array('label' => 'Archivo adjunto','required'  => false))
            ->add('createdAt','hidden')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCase'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bctic_bundle_atencioncrmcambundle_customercase';
    }
}
