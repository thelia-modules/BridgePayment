<?php

namespace BridgePayment\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Thelia\Model\Country;

class BridgeBankEvent extends Event
{
    public const GET_BANKS_EVENT = "brigepayment.event.get_banks";

    protected $banks;

    protected $error;

    /** @var Country */
    protected $country;

    /**
     * @return mixed
     */
    public function getBanks()
    {
        return $this->banks;
    }

    /**
     * @param mixed $banks
     */
    public function setBanks($banks): BridgeBankEvent
    {
        $this->banks = $banks;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error): BridgeBankEvent
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
     */
    public function setCountry($country): BridgeBankEvent
    {
        $this->country = $country;
        return $this;
    }

}