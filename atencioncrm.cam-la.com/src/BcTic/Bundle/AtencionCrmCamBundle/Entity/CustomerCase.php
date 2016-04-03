<?php

namespace BcTic\Bundle\AtencionCrmCamBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * CustomerCase
 *
 *
 */
class CustomerCase
{
  
    /**
     * @var string
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     */
    private $name;

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
     * 
     */
    private $ticket;

    /**
     * @var string
     *
     * 
     */
    private $type;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     */
    private $description;

    /**
     * @var string
     *
     * 
     */
    private $status;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     *      
     */
    private $company;

    /**
     * @var string
     *
     *
     */
    private $companyArea;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Este dato no puede estar vacío") 
     */
    private $companyRole;

    /**
     * 
     *
     * @Assert\File(maxSize="20000000")
     */
    private $file;

   /**
    *
    *
    * 
    */
    private $uploadedFile;


    /**
     * @var timestamp
     *
     * 
     */
    private $createdAt;

    /**
     * @var string
     *
     *
     */
    private $customerPhone;

    /**
     * @var string
     *
     *
     */
    private $resolution;


    public function __construct() {
        $this->createdAt = date("U");
        $this->ticket = -1;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CustomerCase
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

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

    /**
     * Set type
     *
     * @param string $type
     * @return CustomerCase
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set resolution
     *
     * @param string $resolution
     * @return CustomerCase
     */
    public function setResolution($resolution)
    {
        $this->resolution = $resolution;

        return $this;
    }

    /**
     * Get resolution
     *
     * @return string 
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CustomerCase
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return CustomerCase
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set company
     *
     * @param string $company
     * @return CustomerCase
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return string 
     */
    public function getCompany()
    {
        return $this->company;
    }


    /**
     * Set company
     *
     * @param string $company
     * @return CustomerCase
     */
    public function setCompanyArea($companyArea)
    {
        $this->companyArea = $companyArea;

        return $this;
    }

    /**
     * Get company
     *
     * @return string 
     */
    public function getCompanyArea()
    {
        return $this->companyArea;
    }


    /**
     * Set company
     *
     * @param string $company
     * @return CustomerCase
     */
    public function setCompanyRole($companyRole)
    {
        $this->companyRole = $companyRole;

        return $this;
    }

    /**
     * Get company
     *
     * @return string 
     */
    public function getCompanyRole()
    {
        return $this->companyRole;
    }        

    /**
     * Set file
     *
     * @param string $file
     * @return CustomerCase
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return CustomerCase
     */
    public function setUploadedFile($uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;
        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    public function getFullPathUploadedFile(){
        return __DIR__.'/../../../../../web/'.$this->getUploadedFile();
    }

    /**
     * Set createdAt
     *
     * @param timestamp $createdAt
     * @return CustomerCase
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return timestamp
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set customerPhone
     *
     * @param string $customerPhone
     * @return CustomerCase
     */
    public function setCustomerPhone($customerPhone)
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    /**
     * Get customerPhone
     *
     * @return string 
     */
    public function getCustomerPhone()
    {
        return $this->customerPhone;
    }

    protected function getUploadDir()
    {
        return 'uploads/';
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

     public function upload()
    {
      // the file property can be empty if the field is not required
      if (null === $this->getFile()) {
        
      } else {
        $this->getFile()->move(
          $this->getUploadRootDir(),
          $this->getFile()->getClientOriginalName()
        );
        $this->uploadedFile = $this->getUploadDir().$this->getFile()->getClientOriginalName();
        $this->file = null; 
      }

    }
}
