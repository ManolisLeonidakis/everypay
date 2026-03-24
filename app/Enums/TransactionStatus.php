<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
