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

namespace App\Notification;

use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;

final readonly class NotificationModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            EmailService::class,
            SMSService::class,
            PushNotificationService::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(EmailService::class, EmailService::class);
        $container->set(SMSService::class, SMSService::class);
        $container->set(PushNotificationService::class, PushNotificationService::class);
    }
}
