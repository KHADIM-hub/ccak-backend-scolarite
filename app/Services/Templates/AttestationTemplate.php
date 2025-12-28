<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;

class AttestationTemplate implements DocumentTemplateInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Attestation';
    }

    /**
     * @inheritDoc
     */
    public function getView(): string
    {
        return 'pdf.attestation';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredData(): array
    {
        return [
            'attestation_type', // Type d'attestation
            'student_name',
            'student_number',
            'custom_text', // Texte personnalisé principal
            'issue_date',
            'document_number',
        ];
    }

    /**
     * Types d'attestations disponibles
     */
    public function getAttestationTypes(): array
    {
        return [
            'INSCRIPTION' => [
                'name' => 'Attestation d\'Inscription',
                'default_text' => 'est régulièrement inscrit(e) à notre établissement',
                'template' => 'default',
            ],
            'SCOLARITE' => [
                'name' => 'Certificat de Scolarité',
                'default_text' => 'suit régulièrement les cours',
                'template' => 'default',
            ],
            'REUSSITE' => [
                'name' => 'Attestation de Réussite',
                'default_text' => 'a satisfait aux examens',
                'template' => 'success',
            ],
            'STAGE' => [
                'name' => 'Attestation de Stage',
                'default_text' => 'a effectué un stage au sein de notre établissement',
                'template' => 'default',
            ],
            'BONNE_CONDUITE' => [
                'name' => 'Attestation de Bonne Conduite',
                'default_text' => 'a fait preuve d\'une conduite exemplaire',
                'template' => 'default',
            ],
            'FIN_ETUDES' => [
                'name' => 'Attestation de Fin d\'Études',
                'default_text' => 'a terminé avec succès son cursus',
                'template' => 'success',
            ],
            'ABSENCE_CASIER' => [
                'name' => 'Attestation d\'Absence de Sanction',
                'default_text' => 'n\'a fait l\'objet d\'aucune sanction disciplinaire',
                'template' => 'default',
            ],
            'TRANSFERT' => [
                'name' => 'Attestation de Transfert',
                'default_text' => 'est autorisé(e) à poursuivre ses études dans un autre établissement',
                'template' => 'default',
            ],
            'CUSTOM' => [
                'name' => 'Attestation Personnalisée',
                'default_text' => '',
                'template' => 'default',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateData(array $data): bool
    {
        $requiredFields = [
            'attestation_type',
            'student_name',
            'student_number',
            'issue_date',
            'document_number',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validate attestation type
        if (!array_key_exists($data['attestation_type'], $this->getAttestationTypes())) {
            return false;
        }

        // Custom text required for CUSTOM type
        if ($data['attestation_type'] === 'CUSTOM' && empty($data['custom_text'])) {
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
        $types = $this->getAttestationTypes();
        $typeInfo = $types[$data['attestation_type']];

        // Set attestation title
        $processed['attestation_title'] = $typeInfo['name'];
        $processed['template_style'] = $typeInfo['template'];

        // Build custom text
        if (empty($data['custom_text'])) {
            $processed['custom_text'] = $this->buildDefaultText($data, $typeInfo);
        } else {
            $processed['custom_text'] = $this->processCustomText($data['custom_text'], $data);
        }

        // Format dates
        $processed['issue_date_formatted'] = \Carbon\Carbon::parse($data['issue_date'])->locale('fr')->isoFormat('LL');
        $processed['current_date'] = now()->locale('fr')->isoFormat('LL');
        $processed['current_year'] = now()->year;

        // University information
        $processed['university_name'] = config('app.university_name', 'Université Cheikh Anta Diop');
        $processed['university_address'] = config('app.university_address', 'BP 5005, Dakar-Fann, Sénégal');
        $processed['university_phone'] = config('app.university_phone', '+221 33 824 23 79');
        $processed['university_email'] = config('app.university_email', 'contact@ucad.sn');
        $processed['university_website'] = config('app.university_website', 'www.ucad.sn');
        $processed['university_logo'] = asset('images/university-logo.png');

        // Signatures
        $processed['signatory_name'] = $data['signatory_name'] ?? 'Le Directeur des Études';
        $processed['signatory_title'] = $data['signatory_title'] ?? 'Directeur des Études';
        $processed['signature_image'] = $data['signature_image'] ?? asset('images/signature-default.png');

        // Additional seal/stamp
        $processed['official_seal'] = $data['official_seal'] ?? asset('images/official-seal.png');

        // QR Code for verification
        $processed['verification_url'] = route('verify-attestation', $data['document_number']);
        $processed['qr_code_url'] = $this->generateQRCode($processed['verification_url']);

        // Watermark
        $processed['watermark_text'] = 'ORIGINAL';
        $processed['show_watermark'] = $data['show_watermark'] ?? true;

        // Additional fields based on type
        $processed = $this->addTypeSpecificData($processed);

        return $processed;
    }

    /**
     * Build default attestation text
     */
    private function buildDefaultText(array $data, array $typeInfo): string
    {
        $gender = $data['gender'] ?? 'M';
        $article = $gender === 'F' ? 'la' : 'le';
        $participe = $gender === 'F' ? 'née' : 'né';

        $text = "Le Directeur des Études de l'Université Cheikh Anta Diop de Dakar certifie que ";

        // Add gender-specific article
        if (isset($data['use_title']) && $data['use_title']) {
            $text .= ($gender === 'F' ? 'Mademoiselle ' : 'Monsieur ') . "<strong>{$data['student_name']}</strong>, ";
        } else {
            $text .= "<strong>{$data['student_name']}</strong>, ";
        }

        // Add birth info if available
        if (isset($data['date_of_birth']) && isset($data['place_of_birth'])) {
            $birthDate = \Carbon\Carbon::parse($data['date_of_birth'])->locale('fr')->isoFormat('LL');
            $text .= "{$participe}(e) le {$birthDate} à {$data['place_of_birth']}, ";
        }

        // Add student number
        $text .= "matricule <strong>{$data['student_number']}</strong>, ";

        // Add main attestation text
        $text .= $typeInfo['default_text'];

        // Add program and year info
        if (isset($data['program_name'])) {
            $text .= " en <strong>{$data['program_name']}</strong>";
        }

        if (isset($data['academic_year'])) {
            $text .= " pour l'année académique <strong>{$data['academic_year']}</strong>";
        }

        if (isset($data['faculty_name'])) {
            $text .= ", au sein de {$data['faculty_name']}";
        }

        $text .= ".";

        // Add purpose if specified
        if (isset($data['purpose']) && !empty($data['purpose'])) {
            $text .= "<br><br>La présente attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit";

            if ($data['purpose'] !== 'GENERAL') {
                $purposes = [
                    'VISA' => 'notamment pour l\'obtention d\'un visa',
                    'SCHOLARSHIP' => 'notamment pour une demande de bourse',
                    'INTERNSHIP' => 'notamment pour une demande de stage',
                    'HOUSING' => 'notamment pour une demande de logement',
                    'LOAN' => 'notamment pour une demande de prêt',
                    'WORK_PERMIT' => 'notamment pour l\'obtention d\'un permis de travail',
                ];

                if (isset($purposes[$data['purpose']])) {
                    $text .= ", {$purposes[$data['purpose']]}";
                }
            }

            $text .= ".";
        }

        return $text;
    }

    /**
     * Process custom text with variables
     */
    private function processCustomText(string $text, array $data): string
    {
        $variables = [
            '{STUDENT_NAME}' => $data['student_name'],
            '{STUDENT_NUMBER}' => $data['student_number'],
            '{PROGRAM_NAME}' => $data['program_name'] ?? '',
            '{FACULTY_NAME}' => $data['faculty_name'] ?? '',
            '{ACADEMIC_YEAR}' => $data['academic_year'] ?? '',
            '{DEPARTMENT_NAME}' => $data['department_name'] ?? '',
            '{CURRENT_SEMESTER}' => $data['current_semester'] ?? '',
            '{DATE_OF_BIRTH}' => isset($data['date_of_birth'])
                ? \Carbon\Carbon::parse($data['date_of_birth'])->locale('fr')->isoFormat('LL')
                : '',
            '{PLACE_OF_BIRTH}' => $data['place_of_birth'] ?? '',
            '{NATIONALITY}' => $data['nationality'] ?? '',
            '{ENROLLMENT_DATE}' => isset($data['enrollment_date'])
                ? \Carbon\Carbon::parse($data['enrollment_date'])->locale('fr')->isoFormat('LL')
                : '',
        ];

        return str_replace(array_keys($variables), array_values($variables), $text);
    }

    /**
     * Add type-specific data
     */
    private function addTypeSpecificData(array $data): array
    {
        switch ($data['attestation_type']) {
            case 'REUSSITE':
                $data['mention'] = $data['mention'] ?? null;
                $data['average'] = $data['average'] ?? null;
                $data['rank'] = $data['rank'] ?? null;
                break;

            case 'STAGE':
                $data['internship_duration'] = $data['internship_duration'] ?? null;
                $data['internship_company'] = $data['internship_company'] ?? null;
                $data['internship_start_date'] = $data['internship_start_date'] ?? null;
                $data['internship_end_date'] = $data['internship_end_date'] ?? null;
                break;

            case 'FIN_ETUDES':
                $data['graduation_date'] = $data['graduation_date'] ?? null;
                $data['diploma_obtained'] = $data['diploma_obtained'] ?? null;
                break;
        }

        return $data;
    }

    /**
     * Generate QR Code
     */
    private function generateQRCode(string $url): string
    {
        try {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(150)
                ->errorCorrection('M')
                ->generate($url);

            $filename = 'qrcodes/attestation_' . md5($url) . '.png';
            \Storage::disk('public')->put($filename, $qrCode);

            return asset('storage/' . $filename);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function getStyles(): string
    {
        return <<<CSS
        @page {
            margin: 0;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            position: relative;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120pt;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            white-space: nowrap;
        }

        /* Header */
        .document-header {
            border-bottom: 3px solid #1e3a8a;
            padding: 20px 40px;
            margin-bottom: 30px;
            background: linear-gradient(to right, #f8fafc 0%, #e2e8f0 100%);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-logo {
            width: 80px;
            height: 80px;
        }

        .header-info {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .university-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .university-details {
            font-size: 9pt;
            color: #475569;
            line-height: 1.4;
        }

        .header-seal {
            width: 70px;
            height: 70px;
            opacity: 0.8;
        }

        /* Document Reference */
        .document-reference {
            text-align: right;
            padding: 0 40px;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        .reference-number {
            font-weight: bold;
            color: #1e3a8a;
        }

        /* Title */
        .document-title {
            text-align: center;
            padding: 30px 40px;
            margin-bottom: 40px;
        }

        .title-main {
            font-size: 20pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        .title-subtitle {
            font-size: 11pt;
            color: #64748b;
            font-style: italic;
        }

        /* Content */
        .document-content {
            padding: 0 60px;
            margin-bottom: 50px;
            text-align: justify;
            min-height: 300px;
        }

        .content-text {
            font-size: 12pt;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .content-text strong {
            color: #1e3a8a;
            font-weight: bold;
        }

        .additional-info {
            margin-top: 30px;
            padding: 15px;
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
        }

        .additional-info-title {
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .info-row {
            margin: 8px 0;
            padding-left: 20px;
        }

        .info-label {
            display: inline-block;
            min-width: 150px;
            font-weight: bold;
            color: #475569;
        }

        /* Purpose Section */
        .purpose-section {
            margin-top: 30px;
            padding: 20px;
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 5px;
        }

        .purpose-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
            text-align: center;
        }

        /* Signature Section */
        .signature-section {
            padding: 0 60px;
            margin-top: 60px;
        }

        .signature-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-date {
            flex: 1;
            font-size: 11pt;
            font-style: italic;
        }

        .signature-block {
            flex: 1;
            text-align: center;
        }

        .signature-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signature-image {
            width: 120px;
            height: auto;
            margin: 20px auto;
            display: block;
        }

        .signature-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
        }

        .signature-role {
            font-size: 10pt;
            color: #64748b;
            font-style: italic;
        }

        .official-stamp {
            position: relative;
            margin-top: 20px;
        }

        .stamp-image {
            width: 100px;
            height: 100px;
            opacity: 0.7;
            margin: 0 auto;
            display: block;
        }

        /* Footer */
        .document-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 2px solid #1e3a8a;
            padding: 15px 40px;
            background: #f8fafc;
            font-size: 8pt;
            color: #64748b;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-contacts {
            flex: 1;
            line-height: 1.4;
        }

        .footer-qr {
            width: 60px;
            height: 60px;
        }

        /* Success Template (for achievements) */
        .template-success .document-header {
            background: linear-gradient(to right, #ecfdf5 0%, #d1fae5 100%);
            border-bottom-color: #059669;
        }

        .template-success .title-main {
            color: #059669;
        }

        .template-success .document-footer {
            border-top-color: #059669;
        }

        /* Security Features */
        .security-line {
            height: 1px;
            background: repeating-linear-gradient(
                90deg,
                #1e3a8a,
                #1e3a8a 10px,
                transparent 10px,
                transparent 20px
            );
            margin: 20px 0;
        }

        .micro-text {
            font-size: 6pt;
            color: #cbd5e1;
            text-align: center;
            margin: 10px 0;
        }

        /* Print Styles */
        @media print {
            .document-footer {
                position: fixed;
                bottom: 0;
            }
        }
CSS;
    }

    /**
     * Get available variables for custom text
     */
    public function getAvailableVariables(): array
    {
        return [
            '{STUDENT_NAME}' => 'Nom complet de l\'étudiant',
            '{STUDENT_NUMBER}' => 'Numéro matricule',
            '{PROGRAM_NAME}' => 'Nom du programme/filière',
            '{FACULTY_NAME}' => 'Nom de la faculté',
            '{DEPARTMENT_NAME}' => 'Nom du département',
            '{ACADEMIC_YEAR}' => 'Année académique',
            '{CURRENT_SEMESTER}' => 'Semestre actuel',
            '{DATE_OF_BIRTH}' => 'Date de naissance',
            '{PLACE_OF_BIRTH}' => 'Lieu de naissance',
            '{NATIONALITY}' => 'Nationalité',
            '{ENROLLMENT_DATE}' => 'Date d\'inscription',
        ];
    }
}
