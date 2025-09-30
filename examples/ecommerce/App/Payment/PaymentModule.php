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

use App\Database\DatabaseConnection;
use App\Database\DatabaseModule;
use App\Notification\EmailService;
use App\Notification\NotificationModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;

final readonly class PaymentModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function imports(): array
    {
        return [
            ImportItem::create(DatabaseModule::class, DatabaseConnection::class),
            ImportItem::create(NotificationModule::class, EmailService::class),
        ];
    }

    public static function exports(): array
    {
        return [
            \App\Payment\PaymentProcessor::class,
            \App\Payment\PaymentValidator::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(\App\Payment\PaymentProcessor::class, \App\Payment\PaymentProcessor::class);
        $container->set(\App\Payment\PaymentValidator::class, \App\Payment\PaymentValidator::class);
    }
}
