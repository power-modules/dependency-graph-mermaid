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

namespace App\User;

final readonly class UserAuthenticator
{
    public function authenticate(string $username, string $password): bool
    {
        // Dummy auth logic
        return $username !== '' && $password !== '';
    }
}
