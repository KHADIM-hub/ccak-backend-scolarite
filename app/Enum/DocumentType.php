<?php

namespace App\Enum;

enum DocumentType: string
{
    case TRANSCRIPT = 'TRANSCRIPT';
    case CERTIFICATE = 'CERTIFICATE';
    case ATTESTATION = 'ATTESTATION';
    case ID_CARD = 'ID_CARD';
    case DIPLOMA = 'DIPLOMA';
}
