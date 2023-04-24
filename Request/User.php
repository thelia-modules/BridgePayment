<?php

namespace BridgePayment\Request;

use BridgePayment\Service\Provider\SerializerAnnotationsServiceProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class User
{
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var null|string */
    private $companyName;
    /** @var string */
    private $reference;
    /** @var string */
    private $email;
    /** @var string */
    private $uuid;
    /** @var string */
    private $iban;

    /** @Groups({"required"}) */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): User
    {
        $this->firstName = $firstName;
        return $this;
    }

    /** @Groups({"required"}) */
    public function getLastName(): string
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

    /** @Groups({"required"}) */
    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): User
    {
        $this->reference = $reference;
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

    public function getUuid() : ?string
    {
       return $this->uuid;
    }
    public function setUuid(string $uuid): User
    {
       $this->uuid = $uuid;
       return $this;
    }
    public function getIban() : ?string
    {
       return $this->iban;
    }
    public function setIban(string $iban): User
    {
       $this->iban = $iban;
        return $this;
    }
    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        AnnotationRegistry::loadAnnotationClass(Groups::class);
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new ObjectNormalizer($classMetadataFactory)
//            new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())
        ];
        $serializer = new Serializer($normalizers);

        return $serializer->normalize($this, null, ['groups' => ['required']]);
    }
}