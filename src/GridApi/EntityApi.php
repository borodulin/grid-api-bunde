<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi;

use Borodulin\GridApiBundle\GridApi\Expand\ExpandInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityApi implements EntityApiInterface
{
    private ?ExpandInterface $expand = null;
    private NormalizerInterface $normalizer;
    private array $context = [];

    public function __construct(
        NormalizerInterface $normalizer
    ) {
        $this->normalizer = $normalizer;
    }

    public function setContext(array $context): EntityApiInterface
    {
        $this->context = $context;

        return $this;
    }

    public function setExpand(?ExpandInterface $expand): EntityApiInterface
    {
        $this->expand = $expand;

        return $this;
    }

    public function show(object $entity)
    {
        $expand = $this->expand ? $this->expand->getExpand() : [];

        $context = $this->context;
        $context['expand'] = $expand;

        return $this->normalizer->normalize($entity, null, $context);
    }
}
