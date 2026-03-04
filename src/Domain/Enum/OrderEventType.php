<?php

namespace App\Domain\Enum;

enum OrderEventType: string
{
    case INDEXED = 'order.indexed';
    case DELETED = 'order.deleted';
}
