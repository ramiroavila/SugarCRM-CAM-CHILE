<?php

namespace BcTic\VictoriaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserPasswordType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text',array('label' => 'Nombre'))
            ->add('username', 'hidden', array('label' => 'Usuario'))
            ->add('password', 'repeated', array(
                    'first_name'  => 'password',
                    'second_name' => 'repeat_password',
                    'type'        => 'password'))
            ->add('salt','hidden')
            ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BcTic\VictoriaBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bctic_victoriabundle_user';
    }
}
