<?php

namespace App\Enum;

enum DocumentStatus: string
{
    case DRAFT = 'DRAFT';
    case ISSUED = 'ISSUED';
    case REVOKED = 'REVOKED';
}
