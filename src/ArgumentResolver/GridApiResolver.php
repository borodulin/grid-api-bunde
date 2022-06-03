<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\ArgumentResolver;

use Borodulin\GridApiBundle\GridApi\Expand\ExpandFactory;
use Borodulin\GridApiBundle\GridApi\Filter\FilterFactory;
use Borodulin\GridApiBundle\GridApi\GridApi;
use Borodulin\GridApiBundle\GridApi\GridApiInterface;
use Borodulin\GridApiBundle\GridApi\Pagination\PaginationFactory;
use Borodulin\GridApiBundle\GridApi\Sort\SortFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GridApiResolver implements ArgumentValueResolverInterface
{
    private NormalizerInterface $normalizer;
    private SortFactory $sortRequestFactory;
    private ExpandFactory $expandRequestFactory;
    private FilterFactory $filterRequestFactory;
    private PaginationFactory $paginationRequestFactory;

    public function __construct(
        NormalizerInterface $normalizer,
        SortFactory $sortRequestFactory,
        ExpandFactory $expandRequestFactory,
        FilterFactory $filterRequestFactory,
        PaginationFactory $paginationRequestFactory
    ) {
        $this->normalizer = $normalizer;
        $this->sortRequestFactory = $sortRequestFactory;
        $this->expandRequestFactory = $expandRequestFactory;
        $this->filterRequestFactory = $filterRequestFactory;
        $this->paginationRequestFactory = $paginationRequestFactory;
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
        $sort = $this->sortRequestFactory->tryCreateFromInputBug($request->query);
        $expand = $this->expandRequestFactory->tryCreateFromInputBug($request->query);
        $pagination = $this->paginationRequestFactory->createFromInputBug($request->query);
        $filter = $this->filterRequestFactory->tryCreateFromInputBug($request->query);

        yield (new GridApi(
            $this->normalizer,
            $this->paginationRequestFactory
        ))
            ->setExpand($expand)
            ->setSort($sort)
            ->setFilter($filter)
            ->setPagination($pagination)
        ;
    }
}
