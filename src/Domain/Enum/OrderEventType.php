<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum OrderEventType: string
{
    case INDEXED = 'order.indexed';
    case DELETED = 'order.deleted';
    case EMAIL_NOTIFICATION = 'order.email_notification';
}
