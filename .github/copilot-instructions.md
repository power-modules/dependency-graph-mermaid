# AI agent guide for dependency-graph-mermaid-renderer

Purpose: This library adds Mermaid renderers to power-modules/dependency-graph. It exposes three renderers (flowchart, class diagram, timeline) and a module that registers them as plugins.

Big picture
- Core inputs/outputs: All renderers implement `Modular\DependencyGraph\Renderer\Renderer` and accept `DependencyGraph` -> string `.mmd` (Mermaid) output.
- Renderers: `src/MermaidGraph.php` (flowchart LR), `src/MermaidClassDiagram.php` (classDiagram TB + YAML frontmatter), `src/MermaidTimeline.php` (Mermaid timeline with phases).
- Plugin wiring: `src/MermaidRendererModule.php` registers the three renderers via `RendererPluginRegistry` for Power Modules plugin discovery.
- Classification: `src/ModuleClassifier.php` heuristically splits modules into Infrastructure vs Domain for timeline sections.

Key conventions and patterns
- Graph/model comes from power-modules/dependency-graph. Use `ModuleNode` shortName for labels; sanitize identifiers with local helpers (avoid spaces/special chars).
- Styling is appended once at file end. Flowchart uses `class <id> independent|unused` lines + `classDef` definitions. ClassDiagram uses `class X:::style` lines + `classDef`.
- Services on edges: show short class names (strip namespace) and truncate via `maxServiceLength`. Exports listing on nodes is optional via constructor flags.
- ClassDiagram frontmatter: YAML `config.class.hideEmptyMembersBox: true` must stay at top; direction is `TB`.
- Timeline: layers computed Kahn-style by imports-as-prereqs. Sections are Infrastructure and Domain from `ModuleClassifier`.

Developer workflows
- Requirements: PHP 8.4.
- Install: `composer install`
- Fast checks: `make test` (phpunit 12), `make phpstan`, `make codestyle`.
- Full CI locally: `make ci` (runs style, static analysis, tests, then generates and verifies example Mermaid files).
- Generate examples: `make diagrams` or run `php examples/ecommerce/generate.php` and `php examples/microservices/generate_microservices.php`. Outputs go to `examples/**/mermaid/*.mmd`.

Examples and reference files
- E-commerce generator: `examples/ecommerce/generate.php` shows renderer construction flags.
- Microservices generator: `examples/microservices/generate_microservices.php` builds a synthetic `DependencyGraph` without the framework.
- Tests: `test/*.php` demonstrate expected strings and styling placement. Use these when changing rendering rules.

Integration hints
- To plug into a Modular app: add `MermaidRendererModule` to your Power Modules app and retrieve a renderer from the `RendererPluginRegistry`/container.
- Renderers return plain strings; write to `.mmd` files and preview with Mermaid Live or VS Code preview.
- Respect plugin metadata via `getPluginMetadata()` when exposing in registries.

Gotchas
- Keep identifier sanitization (alnum + _) to avoid Mermaid parse errors.
- Don’t duplicate classDef lines; define them once at the end after per-node class assignments.
- For tests that manually set `$graph->edges`, use reflection pattern seen in tests; real edges are produced by dependency-graph library during analysis.

When editing
- Mirror patterns verified by tests (labels, directions, stereotypes, truncation). Update tests if intended behavior changes.
- Maintain constructor defaults: showExports=true, showServices=true, maxServiceLength=50 (Graph/ClassDiagram); Timeline defaults title and showCounts.
