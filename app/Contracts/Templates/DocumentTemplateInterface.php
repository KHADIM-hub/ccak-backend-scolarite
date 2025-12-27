<?php

namespace App\Contracts\Templates;

interface DocumentTemplateInterface
{
    /**
     * Get template name
     */
    public function getName(): string;

    /**
     * Get template view path
     */
    public function getView(): string;

    /**
     * Get required data fields
     */
    public function getRequiredData(): array;

    /**
     * Validate template data
     */
    public function validateData(array $data): bool;

    /**
     * Process data before rendering
     */
    public function processData(array $data): array;

    /**
     * Get template-specific CSS
     */
    public function getStyles(): string;
}
