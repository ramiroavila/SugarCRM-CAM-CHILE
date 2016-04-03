<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerCaseSearchType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email','email', array('label' => 'Correo electrónico'))
            ->add('ticket','text', array('label' => '# de caso'))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BcTic\Bundle\AtencionCrmCamBundle\Entity\CustomerCaseSearch'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bctic_bundle_atencioncrmcambundle_customercasesearch';
    }
}
