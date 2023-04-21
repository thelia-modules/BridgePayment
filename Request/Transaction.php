<?php

namespace BridgePayment\Request;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

class Transaction
{
    /** @var float */
    private $amount;
    /** @var string */
    private $currency;
    /** @var string */
    private $label;
    /** @var string */
    private $endToEndId;
    /** @var string */
    private $executionDate;
    /** @var User */
    private $beneficiary;

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): Transaction
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): Transaction
    {
        $this->currency = $currency;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): Transaction
    {
        $this->label = $label;
        return $this;
    }

    public function getEndToEndId(): string
    {
        return $this->endToEndId;
    }

    public function setEndToEndId(string $endToEndId): Transaction
    {
        $this->endToEndId = $endToEndId;
        return $this;
    }

    public function getExecutionDate(): ?string
    {
        return $this->executionDate;
    }

    public function setExecutionDate(string $executionDate): Transaction
    {
        $this->executionDate = $executionDate;
        return $this;
    }
    public function getBeneficiary(): ?User {
       return $this->beneficiary;
    }
    public function setBeneficiary(User $user): Transaction {
       $this->beneficiary = $user;
        return $this;
    }
}