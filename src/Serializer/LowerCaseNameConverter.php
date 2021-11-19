<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class LowerCaseNameConverter implements NameConverterInterface
{
    public function normalize(string $propertyName): string
    {
        return strtolower($propertyName);
    }

    public function denormalize(string $propertyName): string
    {
        return $propertyName;
    }
}
