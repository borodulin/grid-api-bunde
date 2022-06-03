<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\DataProvider;

interface DataProviderInterface
{
    public function getQueryBuilder(): QueryBuilderInterface;
}
