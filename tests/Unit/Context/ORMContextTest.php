<?php

declare(strict_types=1);

namespace Unit\Context;

use Behat\Gherkin\Node\PyStringNode;
use BehatOrmContext\Context\ORMContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ORMContextTest extends TestCase
{
    private const COUNT = 1;
    private const UUID = 'e809639f-011a-4ae0-9ae3-8fcb460fe950';

    /**
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>|\Doctrine\ORM\Mapping\FieldMapping
     */
    private static function fieldMappingFromArray(array $mapping)
    {
        if (\class_exists(\Doctrine\ORM\Mapping\FieldMapping::class)) {
            return \Doctrine\ORM\Mapping\FieldMapping::fromMappingArray($mapping);
        }

        return $mapping;
    }

    private static function postgresPlatform(): AbstractPlatform
    {
        $class = \class_exists(\Doctrine\DBAL\Platforms\PostgreSQLPlatform::class)
            ? \Doctrine\DBAL\Platforms\PostgreSQLPlatform::class
            : \Doctrine\DBAL\Platforms\PostgreSqlPlatform::class;

        return new $class();
    }

    private static function mysqlPlatform(): AbstractPlatform
    {
        $class = \class_exists(\Doctrine\DBAL\Platforms\MySQLPlatform::class)
            ? \Doctrine\DBAL\Platforms\MySQLPlatform::class
            : \Doctrine\DBAL\Platforms\MySqlPlatform::class;

        return new $class();
    }

    private static function sqlitePlatform(): AbstractPlatform
    {
        $class = \class_exists(\Doctrine\DBAL\Platforms\SQLitePlatform::class)
            ? \Doctrine\DBAL\Platforms\SQLitePlatform::class
            : \Doctrine\DBAL\Platforms\SqlitePlatform::class;

        return new $class();
    }

    public function testAndISeeCountInRepository(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        $context->andISeeInRepository(self::COUNT, 'App\Entity\SomeEntity');
    }

    public function testAndISeeCountInRepositoryFailed(): void
    {
        $context = $this->createContext('App\Entity\SomeEntity', self::COUNT);
        $this->expectException(RuntimeException::class);
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
        $this->expectException(RuntimeException::class);
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
        $queryMock = $this->getMockBuilder(AbstractQuery::class)
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
                ->willReturn(self::fieldMappingFromArray([
                    'type' => 'string',
                    'fieldName' => 'field',
                    'columnName' => 'field',
                ]));

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
                ->willReturn(self::fieldMappingFromArray($fieldMapping));
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('isJsonField');
        $method->setAccessible(true);

        $result = $method->invoke($context, $metadata, 'testField');
        self::assertSame($expectedResult, $result);
    }

    public static function jsonFieldDetectionProvider(): array
    {
        $base = ['fieldName' => 'testField', 'columnName' => 'test_field'];

        return [
            'json field' => [array_merge($base, ['type' => 'json']), true, true],
            'json_array field' => [array_merge($base, ['type' => 'json_array']), true, true],
            'string field' => [array_merge($base, ['type' => 'string']), true, false],
            'integer field' => [array_merge($base, ['type' => 'integer']), true, false],
            'non-existent field' => [array_merge($base, ['type' => 'string']), false, false],
        ];
    }

    /**
     * @dataProvider normalizeJsonValueProvider
     *
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

    public static function normalizeJsonValueProvider(): array
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
    public function testAddJsonFieldCondition(AbstractPlatform $platform, string $expectedWhereClause): void
    {
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

    public static function addJsonFieldConditionProvider(): array
    {
        return [
            'postgresql' => [self::postgresPlatform(), 'CONCAT(\'\', e.testField) = :testField_json'],
            'mysql' => [self::mysqlPlatform(), 'JSON_UNQUOTE(e.testField) = :testField_json'],
            'sqlite fallback' => [self::sqlitePlatform(), 'e.testField = :testField_json'],
            'other database fallback' => [new OraclePlatform(), 'e.testField = :testField_json'],
        ];
    }

    public function testAddJsonFieldConditionWithStringValue(): void
    {
        $platform = self::postgresPlatform();

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
        $platform = self::mysqlPlatform();

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
                [
                    $jsonField,
                    self::fieldMappingFromArray([
                        'type' => 'json',
                        'fieldName' => $jsonField,
                        'columnName' => $jsonField,
                    ]),
                ],
                [
                    $regularField,
                    self::fieldMappingFromArray([
                        'type' => 'string',
                        'fieldName' => $regularField,
                        'columnName' => $regularField,
                    ]),
                ],
            ]);

        // Mock platform and connection for JSON field handling
        $platform = self::postgresPlatform();

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

        $andWhereCalls = [];
        $queryBuilder->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $dql) use (&$andWhereCalls, $queryBuilder) {
                $andWhereCalls[] = $dql;

                return $queryBuilder;
            });

        $setParameterCalls = [];
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, $value) use (&$setParameterCalls, $queryBuilder) {
                $setParameterCalls[] = [$name, $value];

                return $queryBuilder;
            });

        // Mock Query
        $query = $this->createMock(AbstractQuery::class);
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

        self::assertSame(
            ['CONCAT(\'\', e.metadata) = :metadata_json', 'e.status = :status'],
            $andWhereCalls,
        );
        self::assertSame(
            [
                ['metadata_json', '{"type":"premium","tags":["important","urgent"]}'],
                ['status', 'active'],
            ],
            $setParameterCalls,
        );
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
            ->willReturn(self::fieldMappingFromArray([
                'type' => 'json',
                'fieldName' => $jsonField,
                'columnName' => $jsonField,
            ]));

        // Mock platform and connection
        $platform = self::mysqlPlatform();

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
        $query = $this->createMock(AbstractQuery::class);
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

    public function testSeeInRepositoryWithEmbeddedPropertyPath(): void
    {
        $expectedProperties = [
            'value.amount' => '500000',
            'value.currency' => 'USD',
        ];

        // Mock ClassMetadata - not called for embedded paths
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::never())
            ->method('hasField');

        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('App\Entity\Balance', 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('count(e)')
            ->willReturnSelf();

        $andWhereCalls = [];
        $queryBuilder->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $dql) use (&$andWhereCalls, $queryBuilder) {
                $andWhereCalls[] = $dql;

                return $queryBuilder;
            });

        $setParameterCalls = [];
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, $value) use (&$setParameterCalls, $queryBuilder) {
                $setParameterCalls[] = [$name, $value];

                return $queryBuilder;
            });

        // Mock Query
        $query = $this->createMock(AbstractQuery::class);
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
            ->with('App\Entity\Balance')
            ->willReturn($metadata);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        $method->invoke($context, 1, 'App\Entity\Balance', $expectedProperties);

        self::assertSame(['e.value.amount = :p0', 'e.value.currency = :p1'], $andWhereCalls);
        self::assertSame([['p0', '500000'], ['p1', 'USD']], $setParameterCalls);
    }

    public function testSeeInRepositoryWithMixedRegularAndEmbeddedProperties(): void
    {
        $expectedProperties = [
            'customerId' => 'customer-123',
            'balanceValue.amount' => '100000',
            'status' => 'active',
        ];

        // Mock ClassMetadata - only called for non-embedded fields
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                ['customerId', true],
                ['status', true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getFieldMapping')
            ->willReturnMap([
                [
                    'customerId',
                    self::fieldMappingFromArray([
                        'type' => 'string',
                        'fieldName' => 'customerId',
                        'columnName' => 'customerId',
                    ]),
                ],
                [
                    'status',
                    self::fieldMappingFromArray([
                        'type' => 'string',
                        'fieldName' => 'status',
                        'columnName' => 'status',
                    ]),
                ],
            ]);

        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('App\Entity\Balance', 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('count(e)')
            ->willReturnSelf();

        $andWhereCalls = [];
        $queryBuilder->expects(self::exactly(3))
            ->method('andWhere')
            ->willReturnCallback(function (string $dql) use (&$andWhereCalls, $queryBuilder) {
                $andWhereCalls[] = $dql;

                return $queryBuilder;
            });

        $setParameterCalls = [];
        $queryBuilder->expects(self::exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, $value) use (&$setParameterCalls, $queryBuilder) {
                $setParameterCalls[] = [$name, $value];

                return $queryBuilder;
            });

        // Mock Query
        $query = $this->createMock(AbstractQuery::class);
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
            ->with('App\Entity\Balance')
            ->willReturn($metadata);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        $method->invoke($context, 1, 'App\Entity\Balance', $expectedProperties);

        self::assertSame(
            ['e.customerId = :customerId', 'e.balanceValue.amount = :p0', 'e.status = :status'],
            $andWhereCalls,
        );
        self::assertSame(
            [['customerId', 'customer-123'], ['p0', '100000'], ['status', 'active']],
            $setParameterCalls,
        );
    }

    public function testSeeInRepositoryWithNullEmbeddedProperty(): void
    {
        $expectedProperties = [
            'value.amount' => null,
        ];

        // Mock ClassMetadata - not called for embedded paths
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::never())
            ->method('hasField');

        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('App\Entity\Balance', 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('count(e)')
            ->willReturnSelf();

        // Expect IS NULL for null value
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.value.amount IS NULL')
            ->willReturnSelf();

        // No setParameter for null values
        $queryBuilder->expects(self::never())
            ->method('setParameter');

        // Mock Query
        $query = $this->createMock(AbstractQuery::class);
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
            ->with('App\Entity\Balance')
            ->willReturn($metadata);

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        $method->invoke($context, 1, 'App\Entity\Balance', $expectedProperties);
    }

    public function testSeeInRepositoryWithEmbeddedPropertyCountMismatch(): void
    {
        $expectedProperties = [
            'value.amount' => '500000',
        ];

        // Mock ClassMetadata
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::never())
            ->method('hasField');

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
            ->with('e.value.amount = :p0')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('p0', '500000')
            ->willReturnSelf();

        // Mock Query - return 0 to trigger exception
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

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

        $context = new ORMContext($entityManager);

        $reflection = new \ReflectionClass($context);
        $method = $reflection->getMethod('seeInRepository');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Real count is 0, not 1');

        $method->invoke($context, 1, 'App\Entity\Balance', $expectedProperties);
    }
}
