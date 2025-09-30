<?php

declare(strict_types=1);

use Modular\DependencyGraph\Graph\DependencyEdge;
use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Graph\ModuleNode;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidClassDiagram;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidGraph;

require_once __DIR__ . '/../../vendor/autoload.php';

$graph = new DependencyGraph();

// Define microservices (no framework setup needed)
$api = new ModuleNode('Micro\\ApiGateway', 'ApiGateway', [
    'App\\Service\\RoutingService',
    'App\\Service\\AuthMiddleware',
], []);
$auth = new ModuleNode('Micro\\Authentication', 'Authentication', [
    'App\\Service\\JWTService',
    'App\\Service\\OAuth2Service',
], []);
$catalog = new ModuleNode('Micro\\ProductCatalog', 'ProductCatalog', [
    'App\\Service\\CatalogService',
    'App\\Service\\SearchService',
], []);
$cart = new ModuleNode('Micro\\ShoppingCart', 'ShoppingCart', [
    'App\\Service\\CartService',
    'App\\Service\\SessionManager',
], []);
$log = new ModuleNode('Micro\\Logging', 'Logging', [
    'App\\Service\\Logger',
    'App\\Service\\MetricsCollector',
], []);

foreach ([$api, $auth, $catalog, $cart, $log] as $m) {
    $graph->addModule($m);
}

// Edges
$edges = [
    new DependencyEdge($api->className, $auth->className, ['App\\Service\\JWTService']),
    new DependencyEdge($api->className, $log->className, ['App\\Service\\Logger']),
    new DependencyEdge($cart->className, $catalog->className, ['App\\Service\\CatalogService']),
    new DependencyEdge($cart->className, $auth->className, ['App\\Service\\JWTService']),
];

$r = new ReflectionClass($graph);
$p = $r->getProperty('edges');
$p->setAccessible(true);
$p->setValue($graph, $edges);

// Output folder
$outDir = __DIR__;

$variants = [
    $outDir . '/mermaid/microservices.mmd' => new MermaidGraph(),
    $outDir . '/mermaid/microservices_class.mmd' => new MermaidClassDiagram(),
];

foreach ($variants as $file => $renderer) {
    file_put_contents($file, $renderer->render($graph));
}

echo "Generated microservices diagrams in " . realpath($outDir . '/mermaid/') . PHP_EOL;
