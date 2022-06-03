<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\DoctrineInteraction;

use Borodulin\GridApiBundle\Serializer\LowerCaseNameConverter;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class QueryBuilderEntityIterator
{
    private NameConverterInterface $nameConverter;

    public function __construct(
    ) {
        $this->nameConverter = new LowerCaseNameConverter();
    }

    public function aliasIterate(QueryBuilder $queryBuilder): iterable
    {
        $em = $queryBuilder->getEntityManager();
        $aliases = [];
        /** @var From $from */
        foreach ($queryBuilder->getDQLPart('from') as $from) {
            $metadata = $em->getClassMetadata($from->getFrom());

            yield '' => [$from->getAlias() => $metadata];

            $aliases[$from->getAlias()] = $metadata;
        }

        $aliasTree = [];

        foreach ($queryBuilder->getDQLPart('join') as $rootAlias => $joinItems) {
            if (isset($aliases[$rootAlias])) {
                /** @var ClassMetadata $parentMetadata */
                $parentMetadata = $aliases[$rootAlias];
            } else {
                continue;
            }

            /** @var Join $join */
            foreach ($joinItems as $join) {
                $exploded = explode('.', $join->getJoin());
                if (2 === \count($exploded)) {
                    [$aliasName, $joinName] = $exploded;
                    if ($aliasName === $rootAlias) {
                        $joinClass = $parentMetadata->getAssociationTargetClass($joinName);
                        $aliases[$join->getAlias()] = $metadata = $em->getClassMetadata($joinClass);
                        $aliasTree[$join->getAlias()] = $joinName;
                        yield $this->nameConverter->normalize($joinName) => [$join->getAlias() => $metadata];
                    } else {
                        if (isset($aliases[$aliasName], $aliasTree[$aliasName])) {
                            $aliasMetadata = $aliases[$aliasName];
                            $aliasTreeName = $aliasTree[$join->getAlias()] = $aliasTree[$aliasName] . '.' . $joinName;
                            $joinClass = $aliasMetadata->getAssociationTargetClass($joinName);
                            $aliases[$join->getAlias()] = $metadata = $em->getClassMetadata($joinClass);
                            yield $this->nameConverter->normalize($aliasTreeName) => [$join->getAlias() => $metadata];
                        }
                    }
                }
            }
        }
    }

    public function fieldsIterate(string $joinName, array $aliasItem): iterable
    {
        $joinName = $joinName ? $joinName . '.' : '';
        /** @var ClassMetadata $metadata */
        foreach ($aliasItem as $alias => $metadata) {
            foreach ($metadata->getFieldNames() as $fieldName) {
                yield $joinName . $this->nameConverter->normalize($fieldName) => $alias . '.' . $fieldName;
            }
            foreach ($metadata->getAssociationNames() as $assocName) {
                if ($metadata->isSingleValuedAssociation($assocName)) {
                    yield $joinName . $this->nameConverter->normalize($assocName) => $alias . '.' . $assocName;
                }
            }
        }
    }
}
