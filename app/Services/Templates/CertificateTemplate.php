<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;

class CertificateTemplate implements DocumentTemplateInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Certificates';
    }

    /**
     * @inheritDoc
     */
    public function getView(): string
    {
        return 'pdf.certificate';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredData(): array
    {
           return [
                     'document_number',
                     'student' => [
                         'student_number',
                         'full_name',
                         'date_of_birth',
                         'place_of_birth',
                         'nationality',
                         'gender',
                         'address',
                     ],
                     'program' => [
                         'name',
                         'level',
                         'department_name',
                         'faculty_name',
                     ],
                     'enrollment' => [
                         'academic_year',
                         'current_semester',
                         'status',
                         'enrollment_date',
                     ],
                     'purpose',
                     'issue_date',
                     'issued_by',
           ];
    }

    /**
      * Préparer les données depuis un GeneratedDocument
      *
      * @param GeneratedDocument $document
      * @return array
      */
     public function prepareDataFromDocument(GeneratedDocument $document): array
     {
         // Charger toutes les relations nécessaires
         $document->loadMissing([
             'student.user',
             'student.enrollments.academicProgram.department.faculty',
             'student.enrollments.academicYear',
             'generatedBy' // L'admin qui a généré
         ]);

         $student = $document->student;

         // Obtenir l'inscription active ou la plus récente
         $enrollment = $student->enrollments()
             ->with(['academicProgram.department.faculty', 'academicYear'])
             ->where('status', 'ACTIVE')
             ->orWhere('status', 'REGISTERED')
             ->latest()
             ->first();

         if (!$enrollment) {
             throw new \Exception("Aucune inscription trouvée pour l'étudiant {$student->student_number}");
         }

         $program = $enrollment->academicProgram;
         $department = $program->department;
         $faculty = $department->faculty;

         return [
             'document_number' => $document->document_number,
             'student' => [
                 'student_number' => $student->student_number,
                 'full_name' => $student->full_name,
                 'gender' => $student->gender === 'M' ? 'Masculin' : 'Féminin',
                 'date_of_birth' => \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y'),
                 'date_of_birth_text' => \Carbon\Carbon::parse($student->date_of_birth)
                     ->locale('fr')
                     ->isoFormat('D MMMM YYYY'),
                 'place_of_birth' => $student->place_of_birth ?? 'Non renseigné',
                 'nationality' => $student->nationality ?? 'Sénégalaise',
                 'address' => $student->address ?? 'Non renseigné',
                 'phone' => $student->phone ?? 'Non renseigné',
                 'email' => $student->user->email ?? 'Non renseigné',
             ],
             'program' => [
                 'name' => $program->name,
                 'level' => $this->formatLevel($program->level),
                 'duration' => $program->duration_semesters . ' semestres',
                 'total_credits' => $program->total_credits_required,
                 'department_name' => $department->name,
                 'department_code' => $department->code,
                 'faculty_name' => $faculty->name,
                 'faculty_code' => $faculty->code,
             ],
             'enrollment' => [
                 'academic_year' => $enrollment->academicYear->name,
                 'current_semester' => $enrollment->current_semester,
                 'status' => $this->formatEnrollmentStatus($enrollment->status),
                 'enrollment_date' => \Carbon\Carbon::parse($enrollment->enrollment_date)->format('d/m/Y'),
                 'is_scholarship' => $enrollment->is_scholarship,
             ],
             'purpose' => $document->metadata['purpose'] ?? 'Certificat de scolarité',
             'issue_date' => \Carbon\Carbon::parse($document->generated_at)->format('d/m/Y'),
             'issue_date_text' => \Carbon\Carbon::parse($document->generated_at)
                 ->locale('fr')
                 ->isoFormat('D MMMM YYYY'),
             'issued_by' => $document->generatedBy->full_name ?? 'Administration',
             'issued_by_title' => $document->metadata['issued_by_title'] ?? 'Directeur des Études',
             'institution' => [
                 'name' => config('app.institution_name', 'CCAK'),
                 'address' => config('app.institution_address', 'TOUBA, Sénégal'),
                 'phone' => config('app.institution_phone', '+221 XX XXX XX XX'),
                 'email' => config('app.institution_email', 'contact@CCAK.sn'),
             ],
             'created_at' => $document->created_at->format('d/m/Y H:i'),
         ];
    }

    /**
     * Préparer les données depuis un ID de document
     *
     * @param string $documentId (UUID)
     * @return array
     */
    public function prepareDataFromId(string $documentId): array
    {
        $document = GeneratedDocument::with([
            'student.user',
            'student.enrollments.academicProgram.department.faculty',
            'student.enrollments.academicYear',
            'generatedBy'
        ])->findOrFail($documentId);

        return $this->prepareDataFromDocument($document);
    }

    /**
     * Créer un nouveau certificat et générer le document
     *
     * @param array $data
     * @return GeneratedDocument
     */
    public function createCertificate(array $data): GeneratedDocument
    {
        $student = Student::findOrFail($data['student_id']);

        // Générer le numéro de document
        $documentNumber = $this->generateDocumentNumber();

        // Créer l'enregistrement du document
        $document = GeneratedDocument::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'student_id' => $student->id,
            'type' => 'CERTIFICATE',
            'document_number' => $documentNumber,
            'generated_by' => $data['generated_by'] ?? auth()->id(),
            'status' => 'ISSUED',
            'generated_at' => now(),
            'issued_at' => now(),
            'metadata' => [
                'purpose' => $data['purpose'],
                'issued_by_title' => $data['issued_by_title'] ?? 'Directeur des Études',
                'academic_year' => $data['academic_year'] ?? null,
            ],
        ]);

        return $document;
    }

    /**
     * Générer un PDF pour un document spécifique
     *
     * @param GeneratedDocument $document
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdfForDocument(GeneratedDocument $document)
    {
        $data = $this->prepareDataFromDocument($document);

        return app(\App\Services\Templates\DocumentTemplateManager::class)
            ->generatePdf('certificate', $data);
    }

    /**
     * Formater le niveau du programme
     */
    protected function formatLevel(string $level): string
    {
        $levels = [
            'LICENCE' => 'Licence',
            'MASTER' => 'Master',
            'DOCTORAT' => 'Doctorat',
        ];

        return $levels[$level] ?? $level;
    }

    /**
     * Formater le statut d'inscription
     */
    protected function formatEnrollmentStatus(string $status): string
    {
        $statuses = [
            'PENDING' => 'En attente',
            'REGISTERED' => 'Inscrit',
            'ACTIVE' => 'Actif',
            'COMPLETED' => 'Terminé',
            'WITHDRAWN' => 'Retiré',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Générer un numéro de document unique
     */
    protected function generateDocumentNumber(): string
    {
        $prefix = config('certificates.number_prefix', 'CERT');
        $year = date('Y');

        $lastDocument = GeneratedDocument::where('type', 'CERTIFICATE')
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $number = 1;
        if ($lastDocument && preg_match('/-(\d+)$/', $lastDocument->document_number, $matches)) {
            $number = intval($matches[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }

    /**
     * @inheritDoc
     */
    public function validateData(array $data): bool
    {
        $requiredFields = $this->getRequiredData();

        foreach ($requiredFields as $key => $field) {
            if (is_array($field)) {
                if (!isset($data[$key]) || !is_array($data[$key])) {
                    return false;
                }

                foreach ($field as $subField) {
                    if (!isset($data[$key][$subField])) {
                        return false;
                    }
                }
            } else {
                if (!isset($data[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function prepareData(array $data): array
    {
        // Si on reçoit un UUID de document
        if (isset($data['document_id'])) {
            return $this->prepareDataFromId($data['document_id']);
        }

        // Si on reçoit un modèle GeneratedDocument
        if (isset($data['document']) && $data['document'] instanceof GeneratedDocument) {
            return $this->prepareDataFromDocument($data['document']);
        }

        // Utiliser les données brutes
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getValidationErrors(array $data): array
    {
        $errors = [];
        $requiredFields = $this->getRequiredData();

        foreach ($requiredFields as $key => $field) {
            if (is_array($field)) {
                if (!isset($data[$key]) || !is_array($data[$key])) {
                    $errors[$key] = "Le champ {$key} est requis.";
                    continue;
                }

                foreach ($field as $subField) {
                    if (!isset($data[$key][$subField])) {
                        $errors["{$key}.{$subField}"] = "Le champ {$key}.{$subField} est requis.";
                    }
                }
            } else {
                if (!isset($data[$field])) {
                    $errors[$field] = "Le champ {$field} est requis.";
                }
            }
        }

        return $errors;
    }

 }
