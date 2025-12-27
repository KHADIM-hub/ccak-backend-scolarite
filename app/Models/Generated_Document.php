<?php

namespace App\Models;

use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Generated_Document extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'student_id',
        'type',
        'document_number',
        'file_path',
        'generated_by',
        'metadata',
        'status',
        'generated_at',
        'issued_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'generated_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
