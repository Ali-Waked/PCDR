<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'resource', 'owner_id', 'media_id', 'usage', 'uploaded_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
