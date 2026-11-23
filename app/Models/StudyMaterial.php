<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_title',
        'content_type',
        'class_id',
        'available_for',
        'file_path',
        'file_name',
        'upload_date',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'upload_date' => 'date',
        ];
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
