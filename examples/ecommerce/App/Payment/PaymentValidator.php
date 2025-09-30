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

namespace App\Payment;

final readonly class PaymentValidator
{
    public function validate(array $paymentData): bool
    {
        return isset($paymentData['amount']) && $paymentData['amount'] > 0;
    }
}
