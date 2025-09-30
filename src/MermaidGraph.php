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

use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Graph\ModuleNode;
use Modular\DependencyGraph\Renderer\Renderer;
use Modular\Plugin\PluginMetadata;

class MermaidGraph implements Renderer
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            name: 'Mermaid Renderer',
            description: 'A renderer that visualizes Power Modules dependency graphs using Mermaid syntax.',
            version: '0.1.0',
        );
    }

    public function __construct(
        private bool $showExports = true,
        private bool $showServices = true,
        private int $maxServiceLength = 50,
    ) {
    }

    public function render(DependencyGraph $graph): string
    {
        $output = "graph LR\n";

        // Add nodes with exports information
        foreach ($graph->getModules() as $module) {
            $output .= $this->renderModuleNode($module);
        }

        $output .= "\n";

        // Add edges with imported services
        foreach ($graph->getEdges() as $edge) {
            $output .= $this->renderDependencyEdge($edge, $graph);
        }

        // Add styling (assign classes, then define them once at the end)
        $output .= $this->renderStyling($graph);

        return $output;
    }

    public function getFileExtension(): string
    {
        return 'mmd';
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    public function getDescription(): string
    {
        return 'Mermaid flowchart diagram';
    }

    /**
     * Render a single module node.
     */
    private function renderModuleNode(ModuleNode $module): string
    {
        $nodeId = $this->sanitizeNodeId($module->shortName);
        $label = $module->shortName;

        if ($this->showExports && $module->hasExports()) {
            $exports = array_map(
                fn (string $export): string => $this->extractShortName($export),
                $module->exports,
            );
            $label .= '<br/>exports:<br/>' . implode('<br/>', $exports);
        }

        return "    {$nodeId}[{$label}]\n";
    }

    /**
     * Render a dependency edge between modules.
     */
    private function renderDependencyEdge(
        \Modular\DependencyGraph\Graph\DependencyEdge $edge,
        DependencyGraph $graph,
    ): string {
        $fromModule = $graph->getModule($edge->fromModule);
        $toModule = $graph->getModule($edge->toModule);

        if (!$fromModule || !$toModule) {
            return '';
        }

        $fromId = $this->sanitizeNodeId($fromModule->shortName);
        $toId = $this->sanitizeNodeId($toModule->shortName);

        $label = '';
        if ($this->showServices && !empty($edge->importedServices)) {
            $services = array_map(
                fn (string $service): string => $this->extractShortName($service),
                $edge->importedServices,
            );
            $label = implode(', ', $services);

            if (strlen($label) > $this->maxServiceLength) {
                $label = substr($label, 0, $this->maxServiceLength - 3) . '...';
            }
        }

        if ($label !== '') {
            return "    {$fromId} --> |{$label}| {$toId}\n";
        } else {
            return "    {$fromId} --> {$toId}\n";
        }
    }

    /**
     * Add styling to the Mermaid diagram.
     */
    private function renderStyling(DependencyGraph $graph): string
    {
        $styling = "\n";

        // Build sets for quick lookups
        $independent = [];
        foreach ($graph->getIndependentModules() as $module) {
            $independent[$module->className] = true;
        }
        $unused = [];
        foreach ($graph->getUnusedModules() as $module) {
            $unused[$module->className] = true;
        }

        $hasIndependent = !empty($independent);
        $hasUnused = !empty($unused);

        // Assign classes (non-exclusive: a node can be both independent and unused)
        foreach ($graph->getModules() as $module) {
            $nodeId = $this->sanitizeNodeId($module->shortName);
            if (isset($independent[$module->className])) {
                $styling .= "    class {$nodeId} independent\n";
            }
            if (isset($unused[$module->className])) {
                $styling .= "    class {$nodeId} unused\n";
            }
        }

        // Define styles once at the end
        if ($hasIndependent || $hasUnused) {
            $styling .= "\n";
        }
        if ($hasIndependent) {
            $styling .= "    classDef independent fill:#e1f5fe,stroke:#0277bd,stroke-width:2px\n";
        }
        if ($hasUnused) {
            $styling .= "    classDef unused fill:#fff3e0,stroke:#f57c00,stroke-width:2px\n";
        }

        return $styling;
    }

    /**
     * Sanitize a node ID for Mermaid compatibility.
     */
    private function sanitizeNodeId(string $name): string
    {
        // Remove special characters and replace with underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name) ?? $name;
    }

    /**
     * Extract short name from a full class name.
     */
    private function extractShortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
