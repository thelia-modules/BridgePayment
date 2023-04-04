<?php

namespace BridgePayment\Service;

use Thelia\Core\HttpFoundation\Request;

class Notification
{
    public function handleNotificationRequest(Request $request)
    {
        if (!$requestContent = json_decode($request->getContent(), true)) {
            return;
        }

        if (!$content = $requestContent['content'] ?? null) {
            return;
        }


    }
}