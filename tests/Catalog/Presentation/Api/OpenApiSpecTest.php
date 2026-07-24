<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards the hand-authored OpenAPI document against drift from the actual routes:
 * every registered product endpoint must be represented in docs/openapi.yaml.
 */
final class OpenApiSpecTest extends KernelTestCase
{
    public function testItIsAnOpenApi31Document(): void
    {
        $spec = $this->spec();

        self::assertIsString($spec['openapi']);
        self::assertStringStartsWith('3.1', $spec['openapi']);

        self::assertArrayHasKey('info', $spec);
        self::assertIsArray($spec['info']);
        self::assertNotEmpty($spec['info']['title']);
        self::assertNotEmpty($spec['info']['version']);
    }

    public function testEveryRegisteredProductRouteIsDocumented(): void
    {
        $spec = $this->spec();

        self::assertIsArray($spec['paths']);
        $paths = $spec['paths'];

        $matched = 0;

        foreach ($this->router()->getRouteCollection() as $route) {
            $path = $route->getPath();

            if (!str_starts_with($path, '/api/products')) {
                continue;
            }

            ++$matched;

            self::assertArrayHasKey($path, $paths, sprintf('Path "%s" is missing from the OpenAPI spec.', $path));
            self::assertIsArray($paths[$path]);

            foreach ($route->getMethods() as $method) {
                $operation = strtolower($method);

                self::assertArrayHasKey(
                    $operation,
                    $paths[$path],
                    sprintf('Operation "%s %s" is missing from the OpenAPI spec.', $method, $path),
                );
            }
        }

        // Sanity check that the filter actually exercised the current product surface.
        self::assertGreaterThanOrEqual(7, $matched);
    }

    public function testItDocumentsTheCoreSchemas(): void
    {
        $spec = $this->spec();

        self::assertIsArray($spec['components']);
        self::assertIsArray($spec['components']['schemas']);
        $schemas = $spec['components']['schemas'];

        foreach (['Product', 'ProductList', 'ProductWriteRequest', 'ProblemDetails', 'Violation'] as $schema) {
            self::assertArrayHasKey($schema, $schemas);
        }
    }

    public function testEveryReferenceResolves(): void
    {
        $spec = $this->spec();

        $content = (string) file_get_contents($this->specPath());

        $count = preg_match_all("~\\\$ref:\\s*'#/([^']+)'~", $content, $matches);

        self::assertGreaterThan(0, $count, 'Expected the spec to contain component references.');

        foreach (array_unique($matches[1]) as $reference) {
            $node = $spec;

            foreach (explode('/', $reference) as $segment) {
                self::assertIsArray($node);
                self::assertArrayHasKey($segment, $node, sprintf('Dangling reference "#/%s".', $reference));
                $node = $node[$segment];
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        $spec = Yaml::parseFile($this->specPath());

        self::assertIsArray($spec);

        /* @var array<string, mixed> $spec */
        return $spec;
    }

    private function specPath(): string
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        self::assertIsString($projectDir);

        return $projectDir.'/docs/openapi.yaml';
    }

    private function router(): RouterInterface
    {
        $router = self::getContainer()->get('router');

        self::assertInstanceOf(RouterInterface::class, $router);

        return $router;
    }
}
