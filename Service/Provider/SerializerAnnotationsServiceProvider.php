<?php

namespace BridgePayment\Service\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use OpenApi\Annotations\Discriminator;
use Symfony\Component\Serializer\Annotation\Groups;

class SerializerAnnotationsServiceProvider
{
    public static function register(): void
    {
        AnnotationRegistry::loadAnnotationClass(Discriminator::class);

    }
}