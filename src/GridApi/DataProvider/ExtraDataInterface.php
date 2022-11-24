<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\DataProvider;

interface ExtraDataInterface
{
    public function processResponse($response): array;
}
