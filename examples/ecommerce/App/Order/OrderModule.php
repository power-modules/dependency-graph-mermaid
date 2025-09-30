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

namespace App\Order;

use App\Notification\EmailService;
use App\Notification\NotificationModule;
use App\Payment\PaymentModule;
use App\Payment\PaymentProcessor;
use App\Payment\PaymentValidator;
use App\Product\ProductModule;
use App\Product\ProductService;
use App\User\UserModule;
use App\User\UserService;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;

final readonly class OrderModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function imports(): array
    {
        return [
            ImportItem::create(UserModule::class, UserService::class),
            ImportItem::create(ProductModule::class, ProductService::class),
            ImportItem::create(PaymentModule::class, PaymentProcessor::class, PaymentValidator::class),
            ImportItem::create(NotificationModule::class, EmailService::class),
        ];
    }

    public static function exports(): array
    {
        return [
            \App\Order\OrderService::class,
            \App\Order\OrderRepository::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(\App\Order\OrderService::class, \App\Order\OrderService::class);
        $container->set(\App\Order\OrderRepository::class, \App\Order\OrderRepository::class);
    }
}
