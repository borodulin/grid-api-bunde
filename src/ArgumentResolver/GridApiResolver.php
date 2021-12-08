<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\GridApi;
use Borodulin\Bundle\GridApiBundle\GridApi\GridApiInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GridApiResolver implements ArgumentValueResolverInterface
{
    private string $expandKey;
    private string $pageKey;
    private string $pageSizeKey;
    private string $sortKey;
    private int $defaultPageSize;
    private ScenarioInterface $scenario;
    private NormalizerInterface $normalizer;

    public function __construct(
        ScenarioInterface $scenario,
        NormalizerInterface $normalizer,
        string $expandKey,
        string $pageKey,
        string $pageSizeKey,
        string $sortKey,
        int $defaultPageSize
    ) {
        $this->scenario = $scenario;
        $this->expandKey = $expandKey;
        $this->pageKey = $pageKey;
        $this->pageSizeKey = $pageSizeKey;
        $this->sortKey = $sortKey;
        $this->defaultPageSize = $defaultPageSize;
        $this->normalizer = $normalizer;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();

        if (!$type || !interface_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->isInterface()
            && $reflection->implementsInterface(GridApiInterface::class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $sortRequest = SortRequestFactory::tryCreateFromInputBug($request->query, $this->sortKey);

        $expandRequest = ExpandRequestFactory::tryCreateFromInputBug($request->query, $this->expandKey);

        $paginationRequest = PaginationRequestFactory::createFromInputBug(
            $request->query,
            $this->pageKey,
            $this->pageSizeKey,
            $this->defaultPageSize
        );

        $filterRequest = FilterRequestFactory::tryCreateFromInputBug($request->query, [
            $this->expandKey,
            $this->sortKey,
            $this->pageKey,
            $this->pageSizeKey,
        ]);

        yield (new GridApi(
            $this->scenario,
            $this->normalizer,
            $this->defaultPageSize
        ))
            ->setExpandRequest($expandRequest)
            ->setSortRequest($sortRequest)
            ->setFilterRequest($filterRequest)
            ->setPaginationRequest($paginationRequest)
        ;
    }
}
