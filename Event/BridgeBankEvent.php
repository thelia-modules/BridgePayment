<?php

namespace BridgePayment\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Thelia\Model\Country;

class BridgeBankEvent extends Event
{
    public const GET_BANKS_EVENT = "brigepayment.event.get_banks";

    protected mixed $banks;

    protected mixed $error;

    /** @var Country */
    protected Country $country;

    /**
     * @return mixed
     */
    public function getBanks(): mixed
    {
        return $this->banks;
    }

    /**
     * @param mixed $banks
     * @return BridgeBankEvent
     */
    public function setBanks(mixed $banks): BridgeBankEvent
    {
        $this->banks = $banks;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError(): mixed
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     * @return BridgeBankEvent
     */
    public function setError(mixed $error): BridgeBankEvent
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return BridgeBankEvent
     */
    public function setCountry(mixed $country): BridgeBankEvent
    {
        $this->country = $country;
        return $this;
    }

}