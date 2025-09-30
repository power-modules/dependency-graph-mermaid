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
 * Mermaid timeline renderer.
 *
 * Groups modules into initialization "phases" based on dependency levels.
 * Modules with no imports are Phase 0; importers appear in later phases once
 * all their imported modules are available.
 */
final class MermaidTimeline implements Renderer
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            name: 'Mermaid Timeline Renderer',
            description: 'Renders module boot order as a Mermaid timeline grouped by dependency levels.',
            version: '0.1.0',
        );
    }

    public function __construct(
        private readonly string $title = 'Module initialization timeline',
        private readonly bool $showCounts = true,
    ) {
    }

    public function render(DependencyGraph $graph): string
    {
        // Compute levels via Kahn-like layering using "imports" as prerequisites
        [$levels, $counts] = $this->computeLevels($graph);

        // Classify modules into sections
        $classifier = new ModuleClassifier();
        $classes = $classifier->classify($graph);
        $infraSet = array_fill_keys(array_keys($classes['infrastructure']), true);
        $domainSet = array_fill_keys(array_keys($classes['domain']), true);

        $out = "timeline\n";
        if ($this->title !== '') {
            $out .= "title " . $this->title . "\n";
        }

        // Emit Infrastructure section
        $out .= "section Infrastructure\n";
        $out .= $this->emitPhases($levels, $counts, static function (ModuleNode $m) use ($infraSet): bool {
            return isset($infraSet[$m->className]);
        });

        // Emit Domain section
        $out .= "section Domain\n";
        $out .= $this->emitPhases($levels, $counts, static function (ModuleNode $m) use ($domainSet): bool {
            return isset($domainSet[$m->className]);
        });

        return $out;
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
        return 'Mermaid timeline';
    }

    /**
     * @return array{0: array<int,array<int,ModuleNode>>, 1: array<string,array{0:int,1:int}>}
     *         levels and counts keyed by module class: [exportsCount, importsCount]
     */
    private function computeLevels(DependencyGraph $graph): array
    {
        $modules = $graph->getModules();
        // Build dependency maps: prerequisites (imports) and reverse dependents
        $prereqs = [];
        $dependents = [];
        foreach ($modules as $class => $node) {
            $prereqs[$class] = [];
            $dependents[$class] = [];
        }
        foreach ($graph->getEdges() as $edge) {
            // Edge: from importer -> to dependency
            $from = $edge->fromModule;
            $to = $edge->toModule;
            if (!isset($prereqs[$from])) {
                $prereqs[$from] = [];
            }
            if (!isset($dependents[$to])) {
                $dependents[$to] = [];
            }
            $prereqs[$from][$to] = true;
            $dependents[$to][$from] = true;
        }

        // Count exports/imports for optional labels
        $counts = [];
        foreach ($modules as $class => $node) {
            $counts[$class] = [$node->getExportCount(), $node->getImportCount()];
        }

        // Kahn layering: start with modules that have no prereqs (no imports)
        $inDegree = [];
        foreach ($modules as $class => $node) {
            $inDegree[$class] = isset($prereqs[$class]) ? count($prereqs[$class]) : 0;
        }

        $remaining = $inDegree; // mutable copy
        $levels = [];
        while (true) {
            $current = [];
            foreach ($remaining as $class => $deg) {
                if ($deg === 0) {
                    $current[] = $modules[$class];
                }
            }
            if (empty($current)) {
                break;
            }

            // Add to levels in shortName order for readability
            usort($current, static fn (ModuleNode $a, ModuleNode $b) => $a->shortName <=> $b->shortName);
            $levels[] = $current;

            // Remove current from graph and update remaining degrees
            foreach ($current as $node) {
                $class = $node->className;
                unset($remaining[$class]);
                foreach ($dependents[$class] ?? [] as $depClass => $_) {
                    if (isset($remaining[$depClass])) {
                        $remaining[$depClass] = max(0, $remaining[$depClass] - 1);
                    }
                }
            }
        }

        // Any nodes left indicate cycles or unresolved; put them as the last level for visibility
        if (!empty($remaining)) {
            $last = [];
            foreach (array_keys($remaining) as $class) {
                $last[] = $modules[$class];
            }
            usort($last, static fn (ModuleNode $a, ModuleNode $b) => $a->shortName <=> $b->shortName);
            $levels[] = $last;
        }

        return [$levels, $counts];
    }

    /**
     * @param array{0:int,1:int} $counts
     */
    private function moduleLabel(ModuleNode $module, array $counts): string
    {
        if ($this->showCounts) {
            [$exportCount, $importCount] = $counts;

            return sprintf('%s (exports:%d, imports:%d)', $module->shortName, $exportCount, $importCount);
        }

        return $module->shortName;
    }

    /**
     * @param array<int,array<int,ModuleNode>> $levels
     * @param array<string,array{0:int,1:int}> $counts
     * @param callable(ModuleNode):bool $filter
     */
    private function emitPhases(array $levels, array $counts, callable $filter): string
    {
        $out = '';
        foreach ($levels as $i => $modules) {
            $filtered = array_values(array_filter($modules, $filter));
            if (empty($filtered)) {
                continue;
            }
            $period = sprintf('Phase %d', $i);
            $linePrefix = $period . ' : ';
            $first = true;
            foreach ($filtered as $module) {
                $label = $this->moduleLabel($module, $counts[$module->className] ?? [0, 0]);
                if ($first) {
                    $out .= '  ' . $linePrefix . $label . "\n";
                    $first = false;
                } else {
                    $out .= '            : ' . $label . "\n";
                }
            }
        }

        return $out;
    }
}
