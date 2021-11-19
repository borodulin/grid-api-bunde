<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

use Symfony\Component\HttpFoundation\InputBag;

class PaginationRequestFactory
{
    public static function createFromInputBug(
        InputBag $inputBag,
        string $pageKey,
        string $pageSizeKey,
        int $defaultPageSize
    ): PaginationRequest {
        return new PaginationRequest(
            self::getIntegerQueryParam($inputBag, $pageKey, 0),
            self::getIntegerQueryParam($inputBag, $pageSizeKey, $defaultPageSize)
        );
    }

    private static function getIntegerQueryParam(InputBag $query, string $name, int $default): int
    {
        $value = $query->get($name);
        if ($value && is_numeric($value)) {
            $value = (int) $value;

            return $value <= 0 ? $default : $value;
        }

        return $default;
    }
}
