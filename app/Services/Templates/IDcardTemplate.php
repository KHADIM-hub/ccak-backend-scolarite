<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;

class IDcardTemplate implements DocumentTemplateInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'ID carte';
    }

    /**
     * @inheritDoc
     */
    public function getView(): string
    {
        return 'pdf.IDcard';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredData(): array
    {
        return [
            'student_number',
            'student_name',
            'date_of_birth',
            'nationality',
            'photo_url',
            'program_name',
            'faculty_name',
            'academic_year',
            'enrollment_date',
            'expiry_date',
            'blood_group', // Optional
            'emergency_contact', // Optional
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateData(array $data): bool
    {
        $requiredFields = [
            'student_number',
            'student_name',
            'date_of_birth',
            'photo_url',
            'program_name',
            'academic_year',
            'expiry_date',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validate photo exists
        if (!file_exists(public_path($data['photo_url']))) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function processData(array $data): array
    {
        $processed = $data;

        // Generate QR Code data (JSON format)
        $qrData = json_encode([
            'student_number' => $data['student_number'],
            'name' => $data['student_name'],
            'program' => $data['program_name'] ?? '',
            'valid_until' => $data['expiry_date'],
            'verification_url' => url('/verify/student/' . $data['student_number'])
        ]);

        $processed['qr_code_data'] = $qrData;

        // Generate QR Code URL (using a service like chart.googleapis.com or local library)
        $processed['qr_code_url'] = $this->generateQRCodeUrl($qrData);

        // Generate Barcode (Code 128)
        $processed['barcode_data'] = $data['student_number'];
        $processed['barcode_url'] = $this->generateBarcodeUrl($data['student_number']);

        // Format dates
        $processed['date_of_birth_formatted'] = \Carbon\Carbon::parse($data['date_of_birth'])->format('d/m/Y');
        $processed['enrollment_date_formatted'] = \Carbon\Carbon::parse($data['enrollment_date'])->format('d/m/Y');
        $processed['expiry_date_formatted'] = \Carbon\Carbon::parse($data['expiry_date'])->format('d/m/Y');
        $processed['issue_date_formatted'] = now()->format('d/m/Y');

        // University info
        $processed['university_name'] = config('app.university_name', 'Université Cheikh Anta Diop');
        $processed['university_logo'] = asset('images/university-logo.png');
        $processed['signature_url'] = asset('images/registrar-signature.png');

        return $processed;
    }

    /**
     * Generate QR Code URL
     */
    private function generateQRCodeUrl(string $data): string
    {
        // Option 1: Using Google Charts API (simple but requires internet)
        // return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($data);

        // Option 2: Using local library (recommended)
        // You'll need: composer require simplesoftwareio/simple-qrcode
        // Generate and save QR code to storage
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate($data);

        $filename = 'qrcodes/' . md5($data) . '.png';
        \Storage::disk('public')->put($filename, $qrCode);

        return asset('storage/' . $filename);
    }

    /**
     * Generate Barcode URL
     */
    private function generateBarcodeUrl(string $data): string
    {
        // Using milon/barcode or picqer/php-barcode-generator
        // composer require picqer/php-barcode-generator

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($data, $generator::TYPE_CODE_128);

        $filename = 'barcodes/' . md5($data) . '.png';
        \Storage::disk('public')->put($filename, $barcode);

        return asset('storage/' . $filename);
    }
}
