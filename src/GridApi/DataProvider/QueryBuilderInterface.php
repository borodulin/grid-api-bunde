<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\DataProvider;

interface QueryBuilderInterface extends FilterQueryBuilderInterface, PaginationQueryBuilderInterface, SortQueryBuilderInterface
{
}
