<?php

declare(strict_types=1);

namespace Modular\DependencyGraph\Renderer\Mermaid\Test;

use Modular\DependencyGraph\Graph\DependencyEdge;
use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Graph\ModuleNode;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidTimeline;
use Modular\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

final class MermaidTimelineTest extends TestCase
{
    public function testGetPluginMetadata(): void
    {
        $metadata = MermaidTimeline::getPluginMetadata();
        $this->assertInstanceOf(PluginMetadata::class, $metadata);
        $this->assertSame('Mermaid Timeline Renderer', $metadata->name);
        $this->assertSame('0.1.0', $metadata->version);
    }

    public function testEmptyGraph(): void
    {
        $out = (new MermaidTimeline())->render(new DependencyGraph());
        $this->assertStringStartsWith("timeline\n", $out);
    }

    public function testPhasesAndSections(): void
    {
        $g = new DependencyGraph();
        $cfg = new ModuleNode('Cfg', 'Cfg', ['C'], []);
        $db = new ModuleNode('Db', 'Db', ['D'], []);
        $user = new ModuleNode('User', 'User', ['U'], []);
        $order = new ModuleNode('Order', 'Order', ['O'], []);
        $g->addModule($cfg);
        $g->addModule($db);
        $g->addModule($user);
        $g->addModule($order);
        // edges: user->db, order->user
        $edges = [
            new DependencyEdge('User', 'Db', ['D']),
            new DependencyEdge('Order', 'User', ['U']),
        ];
        $r = new \ReflectionClass($g);
        $p = $r->getProperty('edges');
        $p->setAccessible(true);
        $p->setValue($g, $edges);

        $out = (new MermaidTimeline(title: 't'))->render($g);
        // sections present
        $this->assertStringContainsString("section Infrastructure\n", $out);
        $this->assertStringContainsString("section Domain\n", $out);
        // phases present
        $this->assertStringContainsString('Phase 0', $out);
        $this->assertStringContainsString('Phase 1', $out);
    }
}
