<?php

namespace BridgePayment\Model\Notification;

class Notification
{
    public NotificationContent $content;
    public int $timestamp;
    public string $type;
}