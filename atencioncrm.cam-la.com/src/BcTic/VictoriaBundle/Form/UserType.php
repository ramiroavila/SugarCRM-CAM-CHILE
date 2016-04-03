<?php

namespace BcTic\VictoriaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('name')
            ->add('password', 'repeated', array(
                    'first_name'  => 'password',
                    'second_name' => 'repeat_password',
                    'type'        => 'password'))
            ->add('roles' ,'entity', array(
                  'required' =>true,
                  'multiple' => true,
                  'class' => 'BcTicVictoriaBundle:Role',
                  'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                           ->where('r.name LIKE :role')
                           ->setParameter('role','ROLE_%')
                           ->orderBy('r.id', 'ASC');
                    })
            )
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
