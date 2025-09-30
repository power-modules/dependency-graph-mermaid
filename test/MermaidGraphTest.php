<?php

/**
 * This file is part of the Modular Dependency Graph Mermaid Renderer package.
 *
 * (c) 2025 Evgenii Teterin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Modular\DependencyGraph\Renderer\Mermaid\Test;

use Modular\DependencyGraph\Graph\DependencyEdge;
use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Graph\ModuleNode;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidGraph;
use Modular\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

class MermaidGraphTest extends TestCase
{
    private MermaidGraph $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MermaidGraph();
    }

    public function testGetPluginMetadata(): void
    {
        $metadata = MermaidGraph::getPluginMetadata();

        $this->assertInstanceOf(PluginMetadata::class, $metadata);
        $this->assertSame('Mermaid Renderer', $metadata->name);
        $this->assertSame('0.1.0', $metadata->version);
        $this->assertSame('A renderer that visualizes Power Modules dependency graphs using Mermaid syntax.', $metadata->description);
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('mmd', $this->renderer->getFileExtension());
    }

    public function testGetMimeType(): void
    {
        $this->assertSame('text/plain', $this->renderer->getMimeType());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('Mermaid flowchart diagram', $this->renderer->getDescription());
    }

    public function testConstructorWithDefaultValues(): void
    {
        $renderer = new MermaidGraph();

        // We test this by checking the behavior with a graph that has exports and services
        $graph = $this->createGraphWithExportsAndServices();
        $output = $renderer->render($graph);

        // Should include exports (showExports = true by default)
        $this->assertStringContainsString('exports:', $output);
        // Should include service labels (showServices = true by default)
        $this->assertStringContainsString('|DatabaseService|', $output);
    }

    public function testConstructorWithCustomValues(): void
    {
        $renderer = new MermaidGraph(
            showExports: false,
            showServices: false,
            maxServiceLength: 20,
        );

        $graph = $this->createGraphWithExportsAndServices();
        $output = $renderer->render($graph);

        // Should not include exports
        $this->assertStringNotContainsString('exports:', $output);
        // Should not include service labels
        $this->assertStringNotContainsString('|UserService|', $output);
        // Should still have basic arrow connections
        $this->assertStringContainsString('-->', $output);
    }

    public function testRenderEmptyGraph(): void
    {
        $graph = new DependencyGraph();
        $output = $this->renderer->render($graph);

        $expected = "graph LR\n\n\n";
        $this->assertSame($expected, $output);
    }

    public function testRenderSingleModuleWithoutExports(): void
    {
        $graph = new DependencyGraph();
        $module = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: [],
            imports: [],
        );
        $graph->addModule($module);

        $output = $this->renderer->render($graph);

        $this->assertStringContainsString('graph LR', $output);
        $this->assertStringContainsString('UserModule[UserModule]', $output);
        $this->assertStringNotContainsString('exports:', $output);
    }

    public function testRenderSingleModuleWithExports(): void
    {
        $graph = new DependencyGraph();
        $module = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: [
                'App\\Service\\UserService',
                'App\\Repository\\UserRepository',
            ],
            imports: [],
        );
        $graph->addModule($module);

        $output = $this->renderer->render($graph);

        $this->assertStringContainsString('UserModule[UserModule<br/>exports:<br/>UserService<br/>UserRepository]', $output);
    }

    public function testRenderModulesWithDependencies(): void
    {
        $graph = new DependencyGraph();

        // Create modules
        $userModule = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: ['App\\Service\\UserService'],
            imports: [],
        );

        $databaseModule = new ModuleNode(
            className: 'App\\Module\\DatabaseModule',
            shortName: 'DatabaseModule',
            exports: ['App\\Service\\DatabaseService'],
            imports: [],
        );

        $graph->addModule($userModule);
        $graph->addModule($databaseModule);

        // Manually add edge using reflection since we can't use ImportItem
        $edge = new DependencyEdge(
            fromModule: 'App\\Module\\UserModule',
            toModule: 'App\\Module\\DatabaseModule',
            importedServices: ['App\\Service\\DatabaseService'],
        );

        $reflection = new \ReflectionClass($graph);
        $edgesProperty = $reflection->getProperty('edges');
        $edgesProperty->setAccessible(true);
        $edges = $edgesProperty->getValue($graph);
        $edges[] = $edge;
        $edgesProperty->setValue($graph, $edges);

        $output = $this->renderer->render($graph);

        // Should contain both modules
        $this->assertStringContainsString('UserModule[', $output);
        $this->assertStringContainsString('DatabaseModule[', $output);

        // Should contain dependency arrow with service label
        $this->assertStringContainsString('UserModule --> |DatabaseService| DatabaseModule', $output);
    }

    public function testRenderWithHiddenExports(): void
    {
        $renderer = new MermaidGraph(showExports: false);
        $graph = new DependencyGraph();

        $module = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: ['App\\Service\\UserService'],
            imports: [],
        );
        $graph->addModule($module);

        $output = $renderer->render($graph);

        $this->assertStringContainsString('UserModule[UserModule]', $output);
        $this->assertStringNotContainsString('exports:', $output);
    }

    public function testRenderWithHiddenServices(): void
    {
        $renderer = new MermaidGraph(showServices: false);
        $graph = $this->createGraphWithExportsAndServices();

        $output = $renderer->render($graph);

        // Should have arrows without service labels
        $this->assertStringContainsString('UserModule --> DatabaseModule', $output);
        $this->assertStringNotContainsString('|DatabaseService|', $output);
    }

    public function testRenderWithLongServiceNamesTruncation(): void
    {
        $renderer = new MermaidGraph(maxServiceLength: 20);
        $graph = new DependencyGraph();

        $userModule = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: [],
            imports: [],
        );

        $databaseModule = new ModuleNode(
            className: 'App\\Module\\DatabaseModule',
            shortName: 'DatabaseModule',
            exports: [
                'App\\Service\\VeryLongDatabaseServiceNameThatShouldBeTruncated',
                'App\\Repository\\AnotherVeryLongRepositoryName',
            ],
            imports: [],
        );

        $graph->addModule($userModule);
        $graph->addModule($databaseModule);

        // Manually add edge with long service names
        $edge = new DependencyEdge(
            fromModule: 'App\\Module\\UserModule',
            toModule: 'App\\Module\\DatabaseModule',
            importedServices: [
                'App\\Service\\VeryLongDatabaseServiceNameThatShouldBeTruncated',
                'App\\Repository\\AnotherVeryLongRepositoryName',
            ],
        );

        $reflection = new \ReflectionClass($graph);
        $edgesProperty = $reflection->getProperty('edges');
        $edgesProperty->setAccessible(true);
        $edges = $edgesProperty->getValue($graph);
        $edges[] = $edge;
        $edgesProperty->setValue($graph, $edges);

        $output = $renderer->render($graph);

        // Should truncate long service names in edge labels
        $this->assertStringContainsString('...', $output);
        // The full service name should appear in exports but not in the edge label
        $this->assertStringContainsString('VeryLongDatabaseServiceNameThatShouldBeTruncated', $output); // in exports
        $this->assertStringContainsString('|VeryLongDatabaseS...|', $output); // truncated in edge label
    }

    public function testRenderWithMissingModules(): void
    {
        $graph = new DependencyGraph();

        // Create an edge that references non-existent modules
        $edge = new DependencyEdge(
            fromModule: 'NonExistent\\Module1',
            toModule: 'NonExistent\\Module2',
            importedServices: ['SomeService'],
        );

        // We need to manually add the edge since we can't use addModule
        // This tests the edge case where getModule returns null
        $reflection = new \ReflectionClass($graph);
        $edgesProperty = $reflection->getProperty('edges');
        $edgesProperty->setAccessible(true);
        $edgesProperty->setValue($graph, [$edge]);

        $output = $this->renderer->render($graph);

        // Should not include any edges since modules don't exist
        $this->assertSame("graph LR\n\n\n", $output);
    }

    public function testSanitizeNodeId(): void
    {
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('sanitizeNodeId');
        $method->setAccessible(true);

        $this->assertSame('ValidName', $method->invoke($this->renderer, 'ValidName'));
        $this->assertSame('Invalid_Name', $method->invoke($this->renderer, 'Invalid-Name'));
        $this->assertSame('Module_With_Spaces', $method->invoke($this->renderer, 'Module With Spaces'));
        $this->assertSame('Special___Characters_', $method->invoke($this->renderer, 'Special!@#Characters$'));
        $this->assertSame('', $method->invoke($this->renderer, ''));
    }

    public function testExtractShortName(): void
    {
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('extractShortName');
        $method->setAccessible(true);

        $this->assertSame('UserService', $method->invoke($this->renderer, 'App\\Service\\UserService'));
        $this->assertSame('SimpleClass', $method->invoke($this->renderer, 'SimpleClass'));
        $this->assertSame('Class', $method->invoke($this->renderer, 'Very\\Long\\Namespace\\Path\\Class'));
        $this->assertSame('', $method->invoke($this->renderer, ''));
    }

    public function testRenderStylingWithIndependentModules(): void
    {
        $graph = new DependencyGraph();

        $independentModule = new ModuleNode(
            className: 'App\\Module\\IndependentModule',
            shortName: 'IndependentModule',
            exports: ['App\\Service\\SomeService'],
            imports: [], // No imports = independent
        );

        $graph->addModule($independentModule);
        $output = $this->renderer->render($graph);

        $this->assertStringContainsString('classDef independent fill:#e1f5fe,stroke:#0277bd,stroke-width:2px', $output);
        $this->assertStringContainsString('class IndependentModule independent', $output);
    }

    public function testRenderStylingWithUnusedModules(): void
    {
        $graph = new DependencyGraph();

        // Create two modules where one is unused (not referenced by others)
        $unusedModule = new ModuleNode(
            className: 'App\\Module\\UnusedModule',
            shortName: 'UnusedModule',
            exports: ['App\\Service\\UnusedService'],
            imports: [],
        );

        $userModule = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: [],
            imports: [],
        );

        $graph->addModule($unusedModule);
        $graph->addModule($userModule);

        $output = $this->renderer->render($graph);

        $this->assertStringContainsString('classDef unused fill:#fff3e0,stroke:#f57c00,stroke-width:2px', $output);
        $this->assertStringContainsString('class UnusedModule unused', $output);
        $this->assertStringContainsString('class UserModule unused', $output);
    }

    public function testRenderComplexGraph(): void
    {
        $graph = new DependencyGraph();

        // Create a more complex dependency graph
        $modules = [
            new ModuleNode(
                className: 'App\\Module\\UserModule',
                shortName: 'UserModule',
                exports: ['App\\Service\\UserService'],
                imports: [],
            ),
            new ModuleNode(
                className: 'App\\Module\\DatabaseModule',
                shortName: 'DatabaseModule',
                exports: ['App\\Service\\DatabaseService'],
                imports: [],
            ),
            new ModuleNode(
                className: 'App\\Module\\EmailModule',
                shortName: 'EmailModule',
                exports: ['App\\Service\\EmailService'],
                imports: [],
            ),
            new ModuleNode(
                className: 'App\\Module\\ConfigModule',
                shortName: 'ConfigModule',
                exports: ['App\\Service\\ConfigService'],
                imports: [],
            ),
        ];

        foreach ($modules as $module) {
            $graph->addModule($module);
        }

        // Manually add edges using reflection
        $edges = [
            new DependencyEdge(
                fromModule: 'App\\Module\\UserModule',
                toModule: 'App\\Module\\DatabaseModule',
                importedServices: ['App\\Service\\DatabaseService'],
            ),
            new DependencyEdge(
                fromModule: 'App\\Module\\UserModule',
                toModule: 'App\\Module\\EmailModule',
                importedServices: ['App\\Service\\EmailService'],
            ),
            new DependencyEdge(
                fromModule: 'App\\Module\\EmailModule',
                toModule: 'App\\Module\\ConfigModule',
                importedServices: ['App\\Service\\ConfigService'],
            ),
        ];

        $reflection = new \ReflectionClass($graph);
        $edgesProperty = $reflection->getProperty('edges');
        $edgesProperty->setAccessible(true);
        $edgesProperty->setValue($graph, $edges);

        $output = $this->renderer->render($graph);

        // Verify all modules are present
        $this->assertStringContainsString('UserModule[UserModule', $output);
        $this->assertStringContainsString('DatabaseModule[DatabaseModule', $output);
        $this->assertStringContainsString('EmailModule[EmailModule', $output);
        $this->assertStringContainsString('ConfigModule[ConfigModule', $output);

        // Verify dependencies
        $this->assertStringContainsString('UserModule --> |DatabaseService| DatabaseModule', $output);
        $this->assertStringContainsString('UserModule --> |EmailService| EmailModule', $output);
        $this->assertStringContainsString('EmailModule --> |ConfigService| ConfigModule', $output);

        // Verify styling for independent modules (DatabaseModule and ConfigModule have no imports)
        $this->assertStringContainsString('class DatabaseModule independent', $output);
        $this->assertStringContainsString('class ConfigModule independent', $output);
    }

    private function createGraphWithExportsAndServices(): DependencyGraph
    {
        $graph = new DependencyGraph();

        $userModule = new ModuleNode(
            className: 'App\\Module\\UserModule',
            shortName: 'UserModule',
            exports: ['App\\Service\\UserService'],
            imports: [],
        );

        $databaseModule = new ModuleNode(
            className: 'App\\Module\\DatabaseModule',
            shortName: 'DatabaseModule',
            exports: ['App\\Service\\DatabaseService'],
            imports: [],
        );

        $graph->addModule($userModule);
        $graph->addModule($databaseModule);

        // Manually add edge using reflection since we can't use ImportItem
        $edge = new DependencyEdge(
            fromModule: 'App\\Module\\UserModule',
            toModule: 'App\\Module\\DatabaseModule',
            importedServices: ['App\\Service\\DatabaseService'],
        );

        $reflection = new \ReflectionClass($graph);
        $edgesProperty = $reflection->getProperty('edges');
        $edgesProperty->setAccessible(true);
        $edges = $edgesProperty->getValue($graph);
        $edges[] = $edge;
        $edgesProperty->setValue($graph, $edges);

        return $graph;
    }
}
