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

final readonly class ProductSearchService
{
    public function search(string $query): array
    {
        return [['sku' => 'SKU123', 'name' => 'Match for '.$query]];
    }
}
