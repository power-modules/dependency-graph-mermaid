# Dependency Graph → Mermaid Renderers

[![CI](https://github.com/power-modules/dependency-graph-mermaid/actions/workflows/php.yml/badge.svg)](https://github.com/power-modules/dependency-graph-mermaid/actions/workflows/php.yml)
[![Packagist Version](https://img.shields.io/packagist/v/power-modules/dependency-graph-mermaid)](https://packagist.org/packages/power-modules/dependency-graph-mermaid)
[![PHP Version](https://img.shields.io/packagist/php-v/power-modules/dependency-graph-mermaid)](https://packagist.org/packages/power-modules/dependency-graph-mermaid)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-blue)](#)

Mermaid renderer plugin for power-modules/dependency-graph. It adds three renderers (flowchart, class diagram, timeline) and a module that registers them as plugins for discovery in Modular apps.

- Flowchart: `MermaidGraph` → Mermaid `graph LR`
- Class diagram: `MermaidClassDiagram` → Mermaid `classDiagram` (TB) with YAML frontmatter
- Timeline: `MermaidTimeline` → Mermaid `timeline` grouped by phases and sections

Works great with Mermaid Live and VS Code Mermaid preview.

## Requirements

- PHP 8.4+
- power-modules/dependency-graph ^0.2
- power-modules/framework ^2.1 (when used inside a Modular app)
- power-modules/plugin ^1.0 (for plugin-based discovery)

## Install

```sh
composer require power-modules/dependency-graph-mermaid
```

## Quick start

You can use the renderers through the Plugin Registry inside a Modular app (recommended) or directly (see examples).

```php
use Modular\Framework\App\ModularAppBuilder;
use Modular\DependencyGraph\PowerModule\Setup\DependencyGraphSetup;
use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Renderer\RendererPluginRegistry;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidRendererModule;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup; // from power-modules/plugin

$app = (new ModularAppBuilder(__DIR__))
    ->withModules(
        // your app modules ...
        MermaidRendererModule::class,   // provides Mermaid renderers to the registry
    )
    ->withPowerSetup(
        new DependencyGraphSetup(),
        ...PluginRegistrySetup::withDefaults(), // ensures Plugin Registry is available
    )
    ->build();

$graph = $app->get(DependencyGraph::class);
$registry = $app->get(RendererPluginRegistry::class);

// Create any registered renderer on demand
$renderer = $registry->makePlugin(\Modular\DependencyGraph\Renderer\Mermaid\MermaidClassDiagram::class);
$mermaid = $renderer->render($graph);
```

Tip: If you don’t need plugin discovery, you can instantiate the renderer classes directly and call `render()`.

## Renderers at a glance

All renderers implement `Modular\DependencyGraph\Renderer\Renderer` and return Mermaid text (`.mmd`).

### MermaidGraph (flowchart)

- Direction: `graph LR`
- Node label: module short name, optionally “exports:” list
- Edge label: imported service short names (namespace stripped) with truncation
- Styling: marks “independent” (no imports) and “unused” (no inbound edges)
  - Uses `class <id> independent|unused` per node and a single `classDef` set at the end

Constructor options (defaults shown):

```php
new MermaidGraph(
    showExports: true,
    showServices: true,
    maxServiceLength: 50,
)
```

### MermaidClassDiagram

- YAML frontmatter is emitted at the top to hide empty member boxes:
  - `config.class.hideEmptyMembersBox: true`
- Direction: `direction TB`
- Modules rendered as classes; exports appear as public members
- Imports shown as dashed dependencies: `A ..> B : Service1, Service2`
- Stereotypes: `<<independent>>` and `<<unused>>` emitted once per class
- Styling: `class X:::independent|unused` plus `classDef` at the end

Constructor options:

```php
new MermaidClassDiagram(
    showExports: true,
    showServices: true,
    maxServiceLength: 50,
)
```

### MermaidTimeline

- Mermaid `timeline` with optional title
- Modules grouped into phases (0..N) using import prerequisites (Kahn-style layering)
- Sections: Infrastructure and Domain, computed via a lightweight heuristic classifier
- Optional counts in labels: `Name (exports:X, imports:Y)`

Constructor options:

```php
new MermaidTimeline(
    title: 'Module initialization timeline',
    showCounts: true,
)
```

## Examples

This repo ships runnable examples and a Makefile to generate diagrams.

- E-commerce app: `examples/ecommerce/generate.php`
- Synthetic microservices: `examples/microservices/generate_microservices.php`

Generate example diagrams (flowchart, class diagram, timeline) into `examples/**/mermaid/`:

```sh
make diagrams
```

You can preview the `.mmd` files with:
- Mermaid Live: https://mermaid.live/
- VS Code: “Markdown Preview Mermaid Support” or native Mermaid support

## Notes & conventions

- Identifiers are sanitized to `[A-Za-z0-9_]` to avoid Mermaid parse errors
- Service labels show short class names; long labels are truncated by `maxServiceLength`
- Styling/class definitions are emitted once at the end to keep diagrams clean
- Module classification for the timeline is best-effort; adjust names to influence sections

## Development

Helpful targets:

```sh
make test       # PHPUnit
make phpstan    # Static analysis
make codestyle  # PHP CS Fixer (check)
make diagrams   # Generate example Mermaid files and basic verify
make ci         # Style + static + tests + diagram generation
```

Project layout highlights:
- `src/` — renderers and `MermaidRendererModule`
- `examples/` — end-to-end usage with generated `.mmd` outputs
- `test/` — renderer behavior and formatting expectations

## Related packages

- Framework: https://github.com/power-modules/framework
- Plugin system: https://github.com/power-modules/plugin
- Dependency Graph: https://github.com/power-modules/dependency-graph

## License

MIT License — see `LICENSE`.