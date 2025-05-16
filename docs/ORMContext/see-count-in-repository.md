### I See X Entities in Repository

#### Step Definition:

This step checks if the database contains exactly the specified number of entities for a given entity class.

#### Gherkin Example:

```gherkin
And I see 5 entities "App\Entity\User"
```

#### Description:

This step allows you to verify that a specific number of entities exist in the database. It executes a count query on the database for the specified entity class and verifies that the result matches the expected count.

#### Parameters:

- `count`: The expected number of entities
- `entityClass`: The fully-qualified class name of the entity to check

#### Use Cases:

- Verifying that data setup was successful
- Checking that operations have created/deleted the expected number of records
- Validating database state after data manipulation steps 