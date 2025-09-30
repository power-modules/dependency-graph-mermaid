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

namespace App\Product;

use App\Database\DatabaseConnection;
use App\Database\DatabaseModule;
use App\Database\QueryBuilder;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;

final readonly class ProductModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function imports(): array
    {
        return [
            ImportItem::create(DatabaseModule::class, DatabaseConnection::class, QueryBuilder::class),
        ];
    }

    public static function exports(): array
    {
        return [
            \App\Product\ProductService::class,
            \App\Product\ProductRepository::class,
            \App\Product\ProductSearchService::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(\App\Product\ProductService::class, \App\Product\ProductService::class);
        $container->set(\App\Product\ProductRepository::class, \App\Product\ProductRepository::class);
        $container->set(\App\Product\ProductSearchService::class, \App\Product\ProductSearchService::class);
    }
}
