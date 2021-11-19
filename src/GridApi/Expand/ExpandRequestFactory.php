<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Expand;

use Symfony\Component\HttpFoundation\InputBag;

class ExpandRequestFactory
{
    public static function tryCreateFromInputBug(InputBag $inputBag, string $expandKey): ?ExpandRequest
    {
        if ($inputBag->has($expandKey)) {
            $expand = array_filter(array_map('trim', explode(
                ',',
                (string) $inputBag->get($expandKey)
            )));
            if ($expand) {
                return new ExpandRequest($expand);
            }
        }

        return null;
    }
}
