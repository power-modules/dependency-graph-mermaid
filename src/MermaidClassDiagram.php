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

/**
 * Mermaid class diagram renderer.
 *
 * Renders each module as a class with exported services as members and
 * dependencies as dashed dependency relations with optional service labels.
 */
class MermaidClassDiagram implements Renderer
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            name: 'Mermaid Class Diagram Renderer',
            description: 'Renders dependency graphs as Mermaid classDiagram with modules as classes.',
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
        $output = "---\n";
        $output .= "config:\n";
        $output .= "  class:\n";
        $output .= "    hideEmptyMembersBox: true\n";
        $output .= "---\n";
        $output .= "classDiagram\n";
        $output .= "direction TB\n";

        // Define classes (modules)
        foreach ($graph->getModules() as $module) {
            $output .= $this->renderModuleClass($module, $graph);
        }

        $output .= "\n";

        // Stereotypes (must be outside class blocks)
        $output .= $this->renderStereotypes($graph);

        $output .= "\n";

        // Define dependencies (edges)
        foreach ($graph->getEdges() as $edge) {
            $fromModule = $graph->getModule($edge->fromModule);
            $toModule = $graph->getModule($edge->toModule);

            if (!$fromModule || !$toModule) {
                continue;
            }

            $fromName = $this->sanitizeIdentifier($fromModule->shortName);
            $toName = $this->sanitizeIdentifier($toModule->shortName);

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

            // Dashed dependency relation for imports
            if ($label !== '') {
                $output .= "    {$fromName} ..> {$toName} : {$label}\n";
            } else {
                $output .= "    {$fromName} ..> {$toName}\n";
            }
        }

        // Styling: attach class mappings first, then put classDef at the very end
        $output .= $this->renderClassAssignments($graph);
        $output .= $this->renderClassDefs($graph);

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
        return 'Mermaid class diagram';
    }

    private function renderModuleClass(ModuleNode $module, DependencyGraph $graph): string
    {
        $name = $this->sanitizeIdentifier($module->shortName);

        $lines = [];

        if ($this->showExports && $module->hasExports()) {
            // Add each export as a public member
            foreach ($module->exports as $export) {
                $lines[] = '+ ' . $this->extractShortName($export);
            }
        }

        // Build class block
        $output = "    class {$name} {\n";
        foreach ($lines as $line) {
            $output .= "        {$line}\n";
        }
        $output .= "    }\n";

        return $output;
    }

    /**
     * Convert any string to a Mermaid-safe identifier (for class names and references).
     */
    private function sanitizeIdentifier(string $name): string
    {
        // Replace anything non-alphanumeric or underscore with underscore
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name) ?? $name;
    }

    /**
     * Extract short name from a fully-qualified class name.
     */
    private function extractShortName(string $className): string
    {
        // Split by backslash to get the short class name
        $parts = explode('\\', $className);

        return end($parts);
    }

    private function renderStereotypes(DependencyGraph $graph): string
    {
        $out = '';
        foreach ($graph->getIndependentModules() as $module) {
            $name = $this->sanitizeIdentifier($module->shortName);
            $out .= "    <<independent>> {$name}\n";
        }
        foreach ($graph->getUnusedModules() as $module) {
            $name = $this->sanitizeIdentifier($module->shortName);
            $out .= "    <<unused>> {$name}\n";
        }

        return $out;
    }

    private function renderClassAssignments(DependencyGraph $graph): string
    {
        $out = "\n";
        foreach ($graph->getIndependentModules() as $module) {
            $name = $this->sanitizeIdentifier($module->shortName);
            $out .= "    class {$name}:::independent\n";
        }
        foreach ($graph->getUnusedModules() as $module) {
            $name = $this->sanitizeIdentifier($module->shortName);
            $out .= "    class {$name}:::unused\n";
        }

        return $out;
    }

    private function renderClassDefs(DependencyGraph $graph): string
    {
        $out = "\n";
        $independentModules = $graph->getIndependentModules();
        $unusedModules = $graph->getUnusedModules();

        if (!empty($independentModules)) {
            $out .= "    classDef independent fill:#e1f5fe, stroke:#0277bd, stroke-width:2px;\n";
        }
        if (!empty($unusedModules)) {
            $out .= "    classDef unused fill:#fff3e0, stroke:#f57c00, stroke-width:2px, stroke-dasharray: 2;\n";
        }

        return $out;
    }
}
