<?php

declare(strict_types=1);

namespace BehatOrmContext\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use JsonException;
use RuntimeException;

final class ORMContext implements Context
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @And I see :count entities :entityClass
     *
     * @param class-string $entityClass
     */
    public function andISeeInRepository(int $count, string $entityClass): void
    {
        $this->seeInRepository($count, $entityClass);
    }

    /**
     * @Then I see :count entities :entityClass
     *
     * @param class-string $entityClass
     */
    public function thenISeeInRepository(int $count, string $entityClass): void
    {
        $this->seeInRepository($count, $entityClass);
    }

    /**
     * @And I see entity :entity with id :id
     *
     * @param class-string $entityClass
     */
    public function andISeeEntityInRepositoryWithId(string $entityClass, string $id): void
    {
        $this->seeInRepository(1, $entityClass, ['id' => $id]);
    }

    /**
     * @Then I see entity :entity with id :id
     *
     * @param class-string $entityClass
     */
    public function thenISeeEntityInRepositoryWithId(string $entityClass, string $id): void
    {
        $this->seeInRepository(1, $entityClass, ['id' => $id]);
    }

    /**
     * @Then I see entity :entity with properties:
     * @And I see entity :entity with properties:
     *
     * @param class-string $entityClass
     */
    public function andISeeEntityInRepositoryWithProperties(string $entityClass, PyStringNode $string): void
    {
        $expectedProperties = json_decode(trim($string->getRaw()), true, 512, JSON_THROW_ON_ERROR);
        $this->seeInRepository(1, $entityClass, $expectedProperties);
    }

    /**
     * @param class-string $entityClass
     * @param array<string, mixed> $params
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function seeInRepository(int $count, string $entityClass, ?array $params = null): void
    {
        $query = $this->manager->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('count(e)');

        if (null !== $params) {
            $metadata = $this->manager->getClassMetadata($entityClass);
            $paramIndex = 0;

            foreach ($params as $columnName => $columnValue) {
                $isEmbeddedPath = str_contains($columnName, '.');
                $paramName = $isEmbeddedPath ? 'p' . $paramIndex++ : $columnName;

                if ($columnValue === null) {
                    $query->andWhere(sprintf('e.%s IS NULL', $columnName));
                } elseif (!$isEmbeddedPath && $this->isJsonField($metadata, $columnName)) {
                    // Handle JSON fields with proper DQL (skip for embedded paths)
                    $this->addJsonFieldCondition($query, $columnName, $columnValue);
                } else {
                    $query->andWhere(sprintf('e.%s = :%s', $columnName, $paramName))
                        ->setParameter($paramName, $columnValue);
                }
            }
        }

        $realCount = $query->getQuery()
            ->getSingleScalarResult();

        if ($count !== $realCount) {
            throw new RuntimeException(
                sprintf('Real count is %d, not %d', $realCount, $count),
            );
        }
    }

    /**
     * Check if a field is mapped as JSON type
     *
     * @param ClassMetadata<object> $metadata
     */
    private function isJsonField(ClassMetadata $metadata, string $fieldName): bool
    {
        if (!$metadata->hasField($fieldName)) {
            return false;
        }

        $fieldType = $this->getFieldMappingType($metadata->getFieldMapping($fieldName));

        return \in_array($fieldType, ['json', 'json_array'], true);
    }

    /**
     * ORM 2: array mapping; ORM 3: FieldMapping object.
     */
    private function getFieldMappingType(mixed $fieldMapping): string
    {
        if (\is_array($fieldMapping)) {
            return (string) ($fieldMapping['type'] ?? '');
        }

        if (
            \is_object($fieldMapping)
            && \class_exists(\Doctrine\ORM\Mapping\FieldMapping::class)
            && $fieldMapping instanceof \Doctrine\ORM\Mapping\FieldMapping
        ) {
            return self::normalizeFieldMappingTypeValue($fieldMapping->type);
        }

        throw new RuntimeException('Unsupported field mapping structure.');
    }

    private static function normalizeFieldMappingTypeValue(mixed $type): string
    {
        if (\is_string($type)) {
            return $type;
        }

        if (\is_scalar($type)) {
            return (string) $type;
        }

        throw new RuntimeException('Field mapping type must be scalar or string.');
    }

    /**
     * Add JSON field condition using DQL-compatible functions
     * Uses CONCAT for PostgreSQL to convert JSON to string for comparison
     *
     * @param mixed $expectedValue
     */
    private function addJsonFieldCondition(QueryBuilder $query, string $fieldName, $expectedValue): void
    {
        /** @var AbstractPlatform $platform */
        $platform = $this->manager->getConnection()->getDatabasePlatform();

        // Normalize JSON value - ensure consistent encoding
        $expectedJson = $this->normalizeJsonValue($expectedValue);
        $paramName = $fieldName . '_json';

        if ($this->isPostgreSqlPlatform($platform)) {
            // PostgreSQL: Use CONCAT to convert JSON to string for comparison
            // CONCAT('', field) effectively casts JSON to text in a DQL-compatible way
            $query->andWhere(sprintf('CONCAT(\'\', e.%s) = :%s', $fieldName, $paramName))
                ->setParameter($paramName, $expectedJson);
        } elseif ($this->isMySqlFamilyPlatform($platform)) {
            // MySQL: Use JSON_UNQUOTE to extract JSON as string
            $query->andWhere(sprintf('JSON_UNQUOTE(e.%s) = :%s', $fieldName, $paramName))
                ->setParameter($paramName, $expectedJson);
        } else {
            // Fallback for other databases (SQLite, etc.)
            $query->andWhere(sprintf('e.%s = :%s', $fieldName, $paramName))
                ->setParameter($paramName, $expectedJson);
        }
    }

    private function isPostgreSqlPlatform(AbstractPlatform $platform): bool
    {
        foreach (
            [
                'Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform',
                'Doctrine\\DBAL\\Platforms\\PostgreSqlPlatform',
            ] as $class
        ) {
            if (\class_exists($class) && $platform instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function isMySqlFamilyPlatform(AbstractPlatform $platform): bool
    {
        foreach (
            [
                'Doctrine\\DBAL\\Platforms\\AbstractMySQLPlatform',
                'Doctrine\\DBAL\\Platforms\\MySQLPlatform',
                'Doctrine\\DBAL\\Platforms\\MySqlPlatform',
                'Doctrine\\DBAL\\Platforms\\MariaDBPlatform',
            ] as $class
        ) {
            if (\class_exists($class) && $platform instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize JSON value to ensure consistent comparison
     * This handles arrays, objects, and already-encoded JSON strings
     *
     * @param mixed $value
     */
    private function normalizeJsonValue($value): string
    {
        if (is_string($value)) {
            // If it's already a JSON string, decode and re-encode for normalization
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                return json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $e) {
                // If it's not valid JSON, treat as regular string
                return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            }
        }

        // For arrays/objects, encode with consistent flags
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
