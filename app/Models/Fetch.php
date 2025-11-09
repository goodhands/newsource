<?php

namespace App\Models;

use App\Domain\Sources\Models\Source;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fetch extends Model
{
    protected $fillable = [
        'source_id',
        'pages_fetched',
        'articles_fetched',
        'total_pages_available',
        'http_status_code',
        'error_message',
        'was_rate_limited',
        'retry_after_seconds',
    ];

    protected $casts = [
        'was_rate_limited' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function scopeForSource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('http_status_code', 200);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
