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
use Modular\DependencyGraph\Renderer\Mermaid\MermaidClassDiagram;
use Modular\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

final class MermaidClassDiagramTest extends TestCase
{
    public function testGetPluginMetadata(): void
    {
        $metadata = MermaidClassDiagram::getPluginMetadata();
        $this->assertInstanceOf(PluginMetadata::class, $metadata);
        $this->assertSame('Mermaid Class Diagram Renderer', $metadata->name);
        $this->assertSame('0.1.0', $metadata->version);
    }

    public function testBasicsAndFrontmatter(): void
    {
        $renderer = new MermaidClassDiagram();
        $graph = new DependencyGraph();
        $graph->addModule(new ModuleNode('A', 'A', [], []));
        $out = $renderer->render($graph);
        $this->assertStringStartsWith("---\nconfig:\n  class:\n    hideEmptyMembersBox: true\n---\nclassDiagram\ndirection TB\n", $out);
        $this->assertStringContainsString("class A {\n    }", $out);
    }

    public function testEdgesAndLabels(): void
    {
        $renderer = new MermaidClassDiagram(showExports: false, showServices: true);
        $graph = new DependencyGraph();
        $a = new ModuleNode('A', 'A', [], []);
        $b = new ModuleNode('B', 'B', ['X\\Y\\Z'], []);
        $graph->addModule($a);
        $graph->addModule($b);

        $edge = new DependencyEdge('A', 'B', ['X\\Y\\Z']);
        $r = new \ReflectionClass($graph);
        $p = $r->getProperty('edges');
        $p->setAccessible(true);
        $p->setValue($graph, [$edge]);

        $out = $renderer->render($graph);
        $this->assertStringContainsString('A ..> B : Z', $out);
    }

    public function testStereotypesAndClassDefs(): void
    {
        $renderer = new MermaidClassDiagram(showExports: false);
        $graph = new DependencyGraph();
        $ind = new ModuleNode('Ind', 'Ind', [], []); // independent (no imports)
        $unused = new ModuleNode('Unused', 'Unused', [], []);
        $graph->addModule($ind);
        $graph->addModule($unused);

        $out = $renderer->render($graph);
        $this->assertStringContainsString('<<independent>> Ind', $out);
        $this->assertStringContainsString('<<unused>> Unused', $out);
        $this->assertStringContainsString('class Ind:::independent', $out);
        $this->assertStringContainsString('class Unused:::unused', $out);
        $this->assertStringContainsString('classDef independent', $out);
        $this->assertStringContainsString('classDef unused', $out);
    }
}
