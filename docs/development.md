# Development

## Running tests

Run the full test suite:

```console
docker compose exec php bin/phpunit
```

Database tests use `dama/doctrine-test-bundle`, so each test runs inside a transaction and is rolled back automatically.

## After changing Doctrine migrations

Update the test database:

```console
docker compose exec php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

If the test database does not exist yet:

```console
docker compose exec php bin/console doctrine:database:create --env=test
docker compose exec php bin/console doctrine:migrations:migrate --env=test --no-interaction
```
