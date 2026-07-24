<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Tests\Support\ApiTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Contract regression: asserts that live API responses conform to the schemas published in
 * docs/openapi.yaml (the CE-016 contract). The spec is the single source of truth, so the
 * implementation and its documentation cannot silently drift apart.
 *
 * A full JSON-Schema validator is intentionally avoided (no new dependency); the matcher below
 * covers exactly the shapes the product spec uses. It is deliberately strict about undocumented
 * properties, so an accidentally leaked field (e.g. the internal optimistic-lock `version`)
 * fails the contract.
 */
final class ProductApiContractTest extends ApiTestCase
{
    public function testACreatedProductMatchesTheProductSchema(): void
    {
        $created = $this->postJson('/api/products', $this->validPayload());

        $this->assertCreated();

        $this->assertMatchesSchema($created, 'Product');
    }

    public function testTheListResponseMatchesTheProductListSchema(): void
    {
        $this->postJson('/api/products', $this->validPayload());

        $this->assertCreated();

        $list = $this->getJson('/api/products');

        $this->assertOk();

        $this->assertMatchesSchema($list, 'ProductList');
    }

    public function testTheNotFoundErrorMatchesTheProblemDetailsSchema(): void
    {
        $this->client->request('GET', '/api/products/999999');

        $this->assertStatusCode(404);

        $this->assertMatchesSchema($this->problemBody(), 'ProblemDetails');
    }

    public function testTheValidationErrorMatchesTheProblemDetailsSchema(): void
    {
        $this->client->jsonRequest('POST', '/api/products', [
            'name' => '',
            'slug' => 'Not A Slug',
            'priceAmount' => -1,
            'currency' => 'EUR',
        ]);

        $this->assertStatusCode(422);

        $problem = $this->problemBody();

        $this->assertMatchesSchema($problem, 'ProblemDetails');

        // A validation failure must carry the documented field-level violations.
        self::assertArrayHasKey('violations', $problem);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertMatchesSchema(array $payload, string $schema): void
    {
        $this->assertConformsTo($payload, ['$ref' => '#/components/schemas/'.$schema], $schema);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function assertConformsTo(mixed $value, array $schema, string $path): void
    {
        $schema = $this->dereference($schema);

        if (isset($schema['properties'])) {
            self::assertIsArray($value, sprintf('%s: expected an object.', $path));
            self::assertIsArray($schema['properties']);

            $required = $schema['required'] ?? [];
            self::assertIsArray($required);

            foreach ($required as $property) {
                self::assertArrayHasKey($property, $value, sprintf('%s: missing required property "%s".', $path, $property));
            }

            foreach ($value as $key => $item) {
                self::assertArrayHasKey($key, $schema['properties'], sprintf('%s: undocumented property "%s".', $path, $key));

                $propertySchema = $schema['properties'][$key];
                self::assertIsArray($propertySchema);

                $this->assertConformsTo($item, $propertySchema, sprintf('%s.%s', $path, $key));
            }

            return;
        }

        if (($schema['type'] ?? null) === 'array') {
            self::assertIsArray($value, sprintf('%s: expected an array.', $path));

            $items = $schema['items'] ?? [];
            self::assertIsArray($items);

            foreach ($value as $index => $item) {
                $this->assertConformsTo($item, $items, sprintf('%s[%s]', $path, $index));
            }

            return;
        }

        $this->assertScalarType($value, $schema['type'] ?? null, $path);
    }

    private function assertScalarType(mixed $value, mixed $type, string $path): void
    {
        $allowed = is_array($type) ? $type : [$type];

        foreach ($allowed as $candidate) {
            $matches = match ($candidate) {
                'string' => is_string($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'boolean' => is_bool($value),
                'null' => null === $value,
                default => false,
            };

            if ($matches) {
                return;
            }
        }

        self::fail(sprintf('%s: value does not match documented type %s.', $path, (string) json_encode($type)));
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function dereference(array $schema): array
    {
        if (!isset($schema['$ref'])) {
            return $schema;
        }

        self::assertIsString($schema['$ref']);

        $node = $this->spec();

        foreach (explode('/', ltrim($schema['$ref'], '#/')) as $segment) {
            self::assertIsArray($node);
            self::assertArrayHasKey($segment, $node);
            $node = $node[$segment];
        }

        self::assertIsArray($node);

        /* @var array<string, mixed> $node */
        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function problemBody(): array
    {
        $data = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($data);

        /* @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        self::assertIsString($projectDir);

        $spec = Yaml::parseFile($projectDir.'/docs/openapi.yaml');

        self::assertIsArray($spec);

        /* @var array<string, mixed> $spec */
        return $spec;
    }
}
