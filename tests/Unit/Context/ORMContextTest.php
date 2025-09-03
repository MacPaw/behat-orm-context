<?php

declare(strict_types=1);

namespace Unit\Context;

use Behat\Gherkin\Node\PyStringNode;
use BehatOrmContext\Context\ORMContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ORMContextTest extends TestCase
{
    private const COUNT = 1;
    private const UUID = 'e809639f-011a-4ae0-9ae3-8fcb460fe950';

    public function testAndISeeCountInRepository(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        $context->andISeeInRepository(self::COUNT, 'App\Entity\SomeEntity');
    }

    public function testAndISeeCountInRepositoryFailed(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        self::expectException(RuntimeException::class);
        $context->andISeeInRepository(self::COUNT + 1, 'App\Entity\SomeEntity');
    }

    public function testThenISeeCountInRepository(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        $context->thenISeeInRepository(self::COUNT, 'App\Entity\SomeEntity');
    }

    public function testThenISeeCountInRepositoryFailed(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        self::expectException(RuntimeException::class);
        $context->thenISeeInRepository(self::COUNT + 1, 'App\Entity\SomeEntity');
    }

    public function testThenISeeCountInRepositoryWithId(): void
    {
        $context = $this->createContext(
            'App\Entity\SomeEntity',
            1,
            ['id' => self::UUID],
        );
        $context->thenISeeEntityInRepositoryWithId(
            'App\Entity\SomeEntity',
            self::UUID,
        );
    }

    public function testThenISeeCountInRepositoryWithIdFailed(): void
    {
        $context = $this->createContext(
            'App\Entity\SomeEntity',
            1,
            ['id' => self::UUID],
        );
        $context->andISeeEntityInRepositoryWithId(
            'App\Entity\SomeEntity',
            self::UUID,
        );
    }

    public function testThenISeeEntityInRepositoryWithProperties(): void
    {
        $context = $this->createContext(
            'App\Entity\SomeEntity',
            1,
            [
                'id' => self::UUID,
                'someProperty' => 'someValue',
                'otherProperty' => 'otherValue',
            ],
        );
        $context->andISeeEntityInRepositoryWithProperties(
            'App\Entity\SomeEntity',
            new PyStringNode([
                <<<'PSN'
                {
                    "id": "e809639f-011a-4ae0-9ae3-8fcb460fe950",
                    "someProperty": "someValue",
                    "otherProperty": "otherValue"
                }
                PSN
            ], 1),
        );
    }

    public function testThenISeeEntityInRepositoryWithPropertyNull(): void
    {
        $context = $this->createContext(
            'App\Entity\SomeEntity',
            1,
            [
                'id' => self::UUID,
                'someProperty' => null,
            ],
        );
        $context->andISeeEntityInRepositoryWithProperties(
            'App\Entity\SomeEntity',
            new PyStringNode([
                <<<'PSN'
                {
                    "id": "e809639f-011a-4ae0-9ae3-8fcb460fe950",
                    "someProperty": null
                }
                PSN
            ], 1),
        );
    }

    private function createContext(
        string $entityName,
        int $count = 1,
        ?array $properties = null
    ): ORMContext {
        $queryMock = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryMock->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $entityManagerMock = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock->expects(self::once())
            ->method('from')
            ->with(
                $entityName,
                'e',
            )->willReturn($queryBuilderMock);

        if (null !== $properties) {
            // Mock ClassMetadata for field type checking
            $metadata = $this->createMock(ClassMetadata::class);

            // Only non-null properties trigger field type checking
            $nonNullPropertiesCount = count(array_filter($properties, function ($value) {
                return !is_null($value);
            }));

            $metadata->expects(self::exactly($nonNullPropertiesCount))
                ->method('hasField')
                ->willReturn(true);
            $metadata->expects(self::exactly($nonNullPropertiesCount))
                ->method('getFieldMapping')
                ->willReturn(['type' => 'string']); // Default to non-JSON field for existing tests

            $entityManagerMock->expects(self::once())
                ->method('getClassMetadata')
                ->with($entityName)
                ->willReturn($metadata);

            foreach ($properties as $name => $value) {
                $queryBuilderMock->expects(self::exactly(count($properties)))
                    ->method('andWhere')
                    ->willReturnSelf();
                $setParametersCount = count(array_filter($properties, function ($value) {
                    return !is_null($value);
                }));
                $queryBuilderMock->expects(self::exactly($setParametersCount))
                    ->method('setParameter')
                    ->willReturnSelf();
            }
        }

        $queryBuilderMock->expects(self::once())
            ->method('select')
            ->with('count(e)')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects(self::once())
            ->method('getQuery')
            ->willReturn($queryMock);

        $entityManagerMock->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilderMock);

        return new ORMContext($entityManagerMock);
    }

    /**
     * @dataProvider jsonFieldDetectionProvider
     */
    public function testIsJsonField(array $fieldMapping, bool $hasField, bool $expectedResult): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('hasField')
            ->with('testField')
            ->willReturn($hasField);

        if ($hasField) {
            $metadata->expects(self::once())
                ->method('getFieldMapping')
                ->with('testField')
                ->willReturn($fieldMapping);
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('isJsonField');
        $method->setAccessible(true);

        $result = $method->invoke($context, $metadata, 'testField');
        self::assertSame($expectedResult, $result);
    }

    public function jsonFieldDetectionProvider(): array
    {
        return [
            'json field' => [['type' => 'json'], true, true],
            'json_array field' => [['type' => 'json_array'], true, true],
            'string field' => [['type' => 'string'], true, false],
            'integer field' => [['type' => 'integer'], true, false],
            'non-existent field' => [[], false, false],
        ];
    }

    /**
     * @dataProvider normalizeJsonValueProvider
     * @param mixed $input
     */
    public function testNormalizeJsonValue($input, string $expected): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('normalizeJsonValue');
        $method->setAccessible(true);

        $result = $method->invoke($context, $input);
        self::assertSame($expected, $result);
    }

    public function normalizeJsonValueProvider(): array
    {
        return [
            'array input' => [['key' => 'value'], '{"key":"value"}'],
            'object input' => [(object)['key' => 'value'], '{"key":"value"}'],
            'valid json string' => ['{"key":"value"}', '{"key":"value"}'],
            'nested array' => [['items' => [1, 2, 3]], '{"items":[1,2,3]}'],
            'regular string' => ['not json', '"not json"'],
            'null value' => [null, 'null'],
            'boolean true' => [true, 'true'],
            'boolean false' => [false, 'false'],
            'integer' => [42, '42'],
            'invalid json string' => ['not valid json{', '"not valid json{"'],
        ];
    }

    /**
     * @dataProvider addJsonFieldConditionProvider
     */
    public function testAddJsonFieldCondition(string $platformName, string $expectedWhereClause): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(self::once())
            ->method('getName')
            ->willReturn($platformName);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with($expectedWhereClause)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('testField_json', '{"key":"value"}')
            ->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('addJsonFieldCondition');
        $method->setAccessible(true);

        $method->invoke($context, $queryBuilder, 'testField', ['key' => 'value']);
    }

    public function addJsonFieldConditionProvider(): array
    {
        return [
            'postgresql' => ['postgresql', 'CONCAT(\'\', e.testField) = :testField_json'],
            'mysql' => ['mysql', 'JSON_UNQUOTE(e.testField) = :testField_json'],
            'sqlite fallback' => ['sqlite', 'e.testField = :testField_json'],
            'other database fallback' => ['oracle', 'e.testField = :testField_json'],
        ];
    }

    public function testAddJsonFieldConditionWithStringValue(): void
    {
        $platform = $this->createMock(PostgreSQLPlatform::class);
        $platform->expects(self::once())
            ->method('getName')
            ->willReturn('postgresql');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('CONCAT(\'\', e.testField) = :testField_json')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('testField_json', '"test string"')
            ->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('addJsonFieldCondition');
        $method->setAccessible(true);

        $method->invoke($context, $queryBuilder, 'testField', 'test string');
    }

    public function testAddJsonFieldConditionWithComplexArray(): void
    {
        $platform = $this->createMock(MySQLPlatform::class);
        $platform->expects(self::once())
            ->method('getName')
            ->willReturn('mysql');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('JSON_UNQUOTE(e.metadata) = :metadata_json')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('metadata_json', '{"items":[1,2,3],"nested":{"key":"value"}}')
            ->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('addJsonFieldCondition');
        $method->setAccessible(true);

        $complexData = [
            'items' => [1, 2, 3],
            'nested' => ['key' => 'value']
        ];

        $method->invoke($context, $queryBuilder, 'metadata', $complexData);
    }

    public function testSeeInRepositoryWithJsonFieldProperties(): void
    {
        $jsonField = 'metadata';
        $jsonValue = ['type' => 'premium', 'tags' => ['important', 'urgent']];
        $regularField = 'status';
        $regularValue = 'active';

        $expectedProperties = [
            $jsonField => $jsonValue,
            $regularField => $regularValue
        ];

        // Mock ClassMetadata
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                [$jsonField, true],
                [$regularField, true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getFieldMapping')
            ->willReturnMap([
                [$jsonField, ['type' => 'json']],
                [$regularField, ['type' => 'string']]
            ]);

        // Mock platform and connection for JSON field handling
        $platform = $this->createMock(PostgreSQLPlatform::class);
        $platform->expects(self::once())
            ->method('getName')
            ->willReturn('postgresql');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        // Mock QueryBuilder with expectations for both fields
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('App\Entity\TestEntity', 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('count(e)')
            ->willReturnSelf();

        // Expect two andWhere calls - one for JSON field, one for regular field
        $queryBuilder->expects(self::exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['CONCAT(\'\', e.metadata) = :metadata_json'],
                ['e.status = :status']
            )
            ->willReturnSelf();

        // Expect two setParameter calls
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['metadata_json', '{"type":"premium","tags":["important","urgent"]}'],
                ['status', 'active']
            )
            ->willReturnSelf();

        // Mock Query
        $query = $this->createMock(Query::class);
        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        // Mock EntityManager
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with('App\Entity\TestEntity')
            ->willReturn($metadata);
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $context = new ORMContext($entityManager);

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        // This should not throw any exception
        $method->invoke($context, 1, 'App\Entity\TestEntity', $expectedProperties);
    }

    public function testSeeInRepositoryWithJsonFieldPropertiesCountMismatch(): void
    {
        $jsonField = 'settings';
        $jsonValue = ['theme' => 'dark', 'notifications' => true];

        $expectedProperties = [$jsonField => $jsonValue];

        // Mock ClassMetadata
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('hasField')
            ->with($jsonField)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getFieldMapping')
            ->with($jsonField)
            ->willReturn(['type' => 'json']);

        // Mock platform and connection
        $platform = $this->createMock(MySQLPlatform::class);
        $platform->expects(self::once())
            ->method('getName')
            ->willReturn('mysql');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('JSON_UNQUOTE(e.settings) = :settings_json')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('settings_json', '{"theme":"dark","notifications":true}')
            ->willReturnSelf();

        // Mock Query - return different count to trigger exception
        $query = $this->createMock(Query::class);
        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(0); // Expected 1, but got 0

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        // Mock EntityManager
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $context = new ORMContext($entityManager);

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        // This should throw a RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Real count is 0, not 1');

        $method->invoke($context, 1, 'App\Entity\TestEntity', $expectedProperties);
    }
}
