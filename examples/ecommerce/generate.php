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

use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\PowerModule\Setup\DependencyGraphSetup;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidClassDiagram;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidGraph;
use Modular\DependencyGraph\Renderer\Mermaid\MermaidTimeline;
use Modular\Framework\App\ModularAppBuilder;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new ModularAppBuilder(__DIR__)
    ->withModules(
        \App\User\UserModule::class,
        \App\Product\ProductModule::class,
        \App\Order\OrderModule::class,
        \App\Payment\PaymentModule::class,
        \App\Notification\NotificationModule::class,
        \App\Database\DatabaseModule::class,
    )
    ->withPowerSetup(new DependencyGraphSetup())
    ->build();

$targetDir = __DIR__ . '/mermaid';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$graph = $app->get(DependencyGraph::class);

$variants = [
    'ecommerce_full.mmd' => new MermaidGraph(),
    'ecommerce_clean.mmd' => new MermaidGraph(showExports: false, showServices: true, maxServiceLength: 30),
    'ecommerce_minimal.mmd' => new MermaidGraph(showExports: false, showServices: false),

    'ecommerce_class_full.mmd' => new MermaidClassDiagram(),
    'ecommerce_class_clean.mmd' => new MermaidClassDiagram(showExports: false, showServices: true, maxServiceLength: 30),
    'ecommerce_class_minimal.mmd' => new MermaidClassDiagram(showExports: false, showServices: false),

    'ecommerce_timeline.mmd' => new MermaidTimeline(title: 'E-commerce module initialization timeline'),
];

foreach ($variants as $filename => $renderer) {
    $output = $renderer->render($graph);
    file_put_contents($targetDir . '/' . $filename, $output);
}

echo "Generated Mermaid diagrams in $targetDir\n";
