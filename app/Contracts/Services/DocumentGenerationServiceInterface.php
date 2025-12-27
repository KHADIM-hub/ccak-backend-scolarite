<?php

namespace App\Contracts\Services;

use App\Enum\DocumentType;
use App\Enum\DocumentStatus;
use App\Models\Generated_Document;
use App\Models\User;

interface DocumentGenerationServiceInterface
{
    /**
     * Generate a new document
     */
    public function generate(
        User $student,
        DocumentType $type,
        User $generatedBy,
        array $templateData,
        ?bool $withWatermark = false,
        ?bool $withQrCode = false
    ): Generated_Document;

    /**
     * Get document by number
     */
    public function find(string $documentNumber): ?Generated_Document;

    /**
     * Verify document authenticity
     */
    public function verify(string $documentNumber, array $verificationData = []): bool;

    /**
     * Generate secure document number
     */
    public function generateDocumentNumber(DocumentType $type): string;

    /**
     * Get storage path for document
     */
    public function getStoragePath(DocumentType $type, string $documentNumber): string;
}
