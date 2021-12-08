<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DoctrineInteraction;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomFilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\QueryBuilderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderProxy implements QueryBuilderInterface
{
    private QueryBuilder $queryBuilder;
    private int $paramCounter = 0;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    public function setLimit(int $limit): void
    {
        $this->queryBuilder->setMaxResults($limit);
    }

    public function setOffset(int $offset): void
    {
        $this->queryBuilder->setFirstResult($offset);
    }

    public function queryCount(): int
    {
        $qbCount = (clone $this->queryBuilder)
            ->resetDQLPart('orderBy')
            ->setFirstResult(null)
            ->setMaxResults(null)
        ;
        $alias = $qbCount->getRootAliases()[0];

        return (int) $qbCount->select("count($alias)")->getQuery()->getSingleScalarResult();
    }

    public function fetchAll(): array
    {
        return $this->queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function getFilterMap(?CustomFilterInterface $customFilter): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator();
        $rootAliases = $this->queryBuilder->getRootAliases();

        if (null !== $customFilter) {
            foreach ($customFilter->getFilterFields() as $filterName => $fieldName) {
                if (\is_int($filterName) && \is_string($fieldName)) {
                    $result[$fieldName] = ["$fieldName", null];
                } elseif (\is_string($filterName)) {
                    $result[$filterName] = [$fieldName, null];
                }
            }
        } else {
            foreach ($iterator->aliasIterate($this->queryBuilder) as $aliasItem) {
                /** @var ClassMetadata $metadata */
                foreach ($aliasItem as $alias => $metadata) {
                    if (\in_array($alias, $rootAliases)) {
                        $alias = '';
                    }
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
        }

        return $result;
    }

    public function addFilter(string $fieldName, ?string $fieldType, $filterValue): void
    {
        ++$this->paramCounter;
        $p = 'P_' . $this->paramCounter;
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
                    $this->queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } else {
                    $this->queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            case Types::STRING:
            case Types::TEXT:
            case null:
                if (\is_array($filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } elseif (\is_string($filterValue) && preg_match('/^%(.+)%$/', $filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName LIKE :$p")->setParameter($p, $filterValue);
                } else {
                    $this->queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            default:
                break;
        }
    }

    public function getSortMap(?CustomSortInterface $customSort): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator();

        if (null !== $customSort) {
            foreach ($customSort->getSortFields() as $sortName => $fieldName) {
                $result[$sortName] = $fieldName;
            }
        } else {
            $rootAliases = $this->queryBuilder->getRootAliases();

            foreach ($iterator->aliasIterate($this->queryBuilder) as $alias => $aliasItem) {
                if (\in_array($alias, $rootAliases)) {
                    $alias = '';
                }
                foreach ($iterator->fieldsIterate($alias, $aliasItem) as $sortName => $fieldName) {
                    $result[$sortName] = $fieldName;
                }
            }
        }

        return $result;
    }

    public function addOrderBy(string $sort, string $order): void
    {
        $this->queryBuilder->addOrderBy($sort, $order);
    }

    public function resetOrder(): void
    {
        $this->queryBuilder->resetDQLPart('orderBy');
    }
}
