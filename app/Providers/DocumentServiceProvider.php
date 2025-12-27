<?php

namespace App\Providers;

use App\Enum\DocumentType;
use App\Services\Templates\TemplateManager;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateManager::class, function ($app) {
            $manager = new TemplateManager();

            // Register templates
            $manager->register(DocumentType::CERTIFICATE, new CertificateTemplate());
            // Add more templates here

            return $manager;
        });

        $this->app->singleton(DocumentGenerationServiceInterface::class, PdfGenerationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
