<?php

namespace BridgePayment\Model\Api;

class User
{
    /** @var string */
    protected $firstName;
    /** @var string */
    protected $lastName;
    /** @var null|string */
    protected $companyName;
    /** @var string */
    protected $externalReference;
    /** @var string */
    protected $email;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): User
    {
        $this->firstName = $firstName;
        return $this;
    }
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): User
    {
        $this->lastName = $lastName;
        return $this;
    }
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }
    public function setCompanyName(string $companyName): User
    {
        $this->companyName = $companyName;
        return $this;
    }
    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }
    public function setExternalReference(string $externalReference): User
    {
        $this->externalReference = $externalReference;
        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }
}