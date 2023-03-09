<?php

namespace BridgePayment\Event;

use Symfony\Component\EventDispatcher\Event;
use Thelia\Model\Country;

class BridgeBankEvent extends Event
{
    const GET_BANKS_EVENT = "brigepayment.event.get_banks";

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
     * @return BridgeBankEvent
     */
    public function setBanks($banks)
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
     * @return BridgeBankEvent
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return BridgeBankEvent
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

}