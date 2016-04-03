<?php

namespace BcTic\VictoriaBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use BcTic\VictoriaBundle\Entity\User;
use BcTic\VictoriaBundle\Entity\Role;

class LoadUserData implements FixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

      //Create TWO ROLES
      $role = new Role();
      $role->setName('ROLE_ADMIN');
      $manager->persist($role);

      $user = new User();
      $user->setUsername("admin");
      $user->setName("Administrador");
      $user->setSalt(md5(uniqid()));
      $user->addRole($role);
      $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
      $user->setPassword($encoder->encodePassword('admin', $user->getSalt()));

      $manager->persist($user);

      $manager->flush();
    }
}