### I See Entity with Specific ID

#### Step Definition:

This step checks if the database contains an entity of a specific type with the given ID.

#### Gherkin Example:

```gherkin
And I see entity "App\Entity\Product" with id "abc123"
```

#### Description:

This step allows you to verify that a specific entity exists in the database by its ID. It performs a count query with a condition on the ID field and expects exactly one matching entity.

#### Parameters:

- `entityClass`: The fully-qualified class name of the entity to check
- `id`: The ID value to match against the entity's ID field

#### Use Cases:

- Verifying that a specific record exists after creation
- Confirming entity persistence during a multi-step process
- Checking that an entity with a specific identifier is still present 