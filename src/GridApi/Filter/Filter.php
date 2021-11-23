<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\QueryBuilderEntityIterator;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\Serializer\LowerCaseNameConverter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class Filter
{
    private NameConverterInterface $nameConverter;
    private int $counter = 0;
    private EntityConverterRegistry $entityConverterRegistry;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry
    ) {
        $this->nameConverter = new LowerCaseNameConverter();
        $this->entityConverterRegistry = $entityConverterRegistry;
    }

    public function filter(
        FilterRequestInterface $filterRequest,
        QueryBuilder $queryBuilder
    ): QueryBuilder {
        $queryBuilder = clone $queryBuilder;

        $filterMap = $this->getFilterMap($queryBuilder);

        foreach ($filterRequest->getFilters() as $name => $filterValue) {
            $name = $this->nameConverter->normalize($name);
            if (isset($filterMap[$name])) {
                [$fieldName, $fieldType] = $filterMap[$name];
                if (\is_callable($fieldName)) {
                    \call_user_func($fieldName, $queryBuilder, $filterValue);
                } else {
                    $this->addFilter($queryBuilder, $fieldName, $fieldType, $filterValue);
                }
            }
        }

        return $queryBuilder;
    }

    private function getFilterMap(QueryBuilder $queryBuilder): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator($this->nameConverter);

        foreach ($iterator->aliasIterate($queryBuilder) as $alias => $aliasItem) {
            /** @var ClassMetadata $metadata */
            $metadata = array_values($aliasItem)[0];

            $filterableFields = $this->entityConverterRegistry
                ->getCustomFilterFieldsForClass($metadata->getReflectionClass()->getName());

            if ($filterableFields) {
                foreach ($filterableFields->getFilterFields() as $filterName => $fieldName) {
                    $result[$filterName] = [$fieldName, null];
                }
            } else {
                foreach ($iterator->fieldsIterate($alias, $aliasItem) as $filterName => $fieldName) {
                    [, $realName] = explode('.', $fieldName);
                    $filterName = str_replace('.', '_', $filterName);
                    if ($metadata->isSingleValuedAssociation($realName)) {
                        $result[$filterName] = [$fieldName, null];
                    } else {
                        $result[$filterName] = [$fieldName, $metadata->getTypeOfField($realName)];
                    }
                }
            }
        }

        return $result;
    }

    private function addFilter(QueryBuilder $queryBuilder, string $fieldName, ?string $fieldType, $filterValue): void
    {
        ++$this->counter;
        $p = 'P_' . $this->counter;
        if (\is_string($filterValue)) {
            $filterValueArr = array_filter(explode(',', $filterValue), 'trim');
            $filterValue = \count($filterValueArr) > 1 ? $filterValueArr : $filterValue;
        }
        switch ($fieldType) {
            case Types::BOOLEAN:
            case Types::DATETIME_MUTABLE:
            case Types::DATETIMETZ_MUTABLE:
            case Types::DATETIME_IMMUTABLE:
            case Types::DATETIMETZ_IMMUTABLE:
            case Types::DATE_MUTABLE:
            case Types::DATE_IMMUTABLE:
            case Types::TIME_MUTABLE:
            case Types::TIME_IMMUTABLE:
            case Types::DECIMAL:
            case Types::FLOAT:
            case Types::INTEGER:
            case Types::BIGINT:
            case Types::SMALLINT:
                if (\is_array($filterValue)) {
                    $queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } else {
                    $queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            case Types::STRING:
            case Types::TEXT:
            case null:
                if (\is_array($filterValue)) {
                    $queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } elseif (\is_string($filterValue) && preg_match('/^%(.+)%$/', $filterValue)) {
                    $queryBuilder->andWhere("$fieldName LIKE :$p")->setParameter($p, $filterValue);
                } else {
                    $queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            default:
                break;
        }
    }
}
