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

namespace Modular\DependencyGraph\Renderer\Mermaid;

use Modular\DependencyGraph\Renderer\Renderer;
use Modular\DependencyGraph\Renderer\RendererPluginRegistry;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Plugin\Contract\ProvidesPlugins;

/**
 * @implements ProvidesPlugins<Renderer>
 */
class MermaidRendererModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            RendererPluginRegistry::class => [
                MermaidGraph::class,
                MermaidClassDiagram::class,
                MermaidTimeline::class,
            ],
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(MermaidGraph::class, MermaidGraph::class);
        $container->set(MermaidClassDiagram::class, MermaidClassDiagram::class);
        $container->set(MermaidTimeline::class, MermaidTimeline::class);
    }
}
