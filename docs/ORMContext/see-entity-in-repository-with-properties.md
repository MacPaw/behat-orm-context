### I See Entity with Specific Properties

#### Step Definition:

This step checks if the database contains an entity of a specific type with the given properties.

#### Gherkin Example:

```gherkin
Then I see entity "App\Entity\User" with properties:
    """
    {
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "active": true
    }
    """
```

#### Description:

This step allows you to verify that a specific entity exists in the database by matching multiple property values. It performs a count query with conditions on each specified property and expects exactly one matching entity.

#### Parameters:

- `entityClass`: The fully-qualified class name of the entity to check
- `properties`: A JSON string containing key-value pairs representing entity properties to match

#### Use Cases:

- Verifying that an entity with specific field values exists
- Checking that entity properties match expected values after operations
- Validating complex entity state with multiple property conditions
- Testing business logic that modifies entity properties

#### Notes:

- Properties with `null` values are queried using `IS NULL` condition
- All other properties are matched using equality 