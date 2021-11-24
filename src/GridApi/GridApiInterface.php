<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationResponseInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortRequestInterface;

interface GridApiInterface
{
    public function setFilterRequest(?FilterRequestInterface $filterRequest): self;

    public function setSortRequest(?SortRequestInterface $sortRequest): self;

    public function setPaginationRequest(PaginationRequestInterface $paginationRequest): self;

    public function setExpandRequest(?ExpandRequestInterface $expandRequest): self;

    public function setScenario(ScenarioInterface $scenario): self;

    public function paginate(
        DataProviderInterface $dataProvider
    ): PaginationResponseInterface;

    public function listAll(DataProviderInterface $dataProvider): array;
}
