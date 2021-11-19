<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

use Symfony\Component\HttpFoundation\InputBag;

class FilterRequestFactory
{
    public static function tryCreateFromInputBug(InputBag $inputBag, array $ignored): ?FilterRequest
    {
        $filters = self::getFilterQueryParams($inputBag, $ignored);
        if ($filters) {
            return new FilterRequest($filters);
        }

        return null;
    }

    private static function getFilterQueryParams(InputBag $inputBag, array $ignored): array
    {
        $result = [];
        foreach ($inputBag->all() as $key => $value) {
            if (!\in_array($key, $ignored)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
