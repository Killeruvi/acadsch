<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_title',
        'book_number',
        'isbn_number',
        'publisher_name',
        'author_name',
        'subject_id',
        'rack_number',
        'quantity',
        'available_quantity',
        'book_price',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'available_quantity' => 'integer',
            'book_price' => 'decimal:2',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class, 'book_id');
    }
}
