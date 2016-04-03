<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * CustomerCase
 *
 *
 */
class CustomerCaseSearch
{
  
    /**
     * @var string
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     * @Assert\Email(message="El email ingresado no es correcto")
     */
    private $email;

    /**
     * @var integer
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     * 
     */
    private $ticket;


    /**
     * Set email
     *
     * @param string $email
     * @return CustomerCase
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set ticket
     *
     * @param integer $ticket
     * @return CustomerCase
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return integer 
     */
    public function getTicket()
    {
        return $this->ticket;
    }

}
