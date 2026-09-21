<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'dispatcher_id',
    'titre',
    'date_rapport',
    'fichier_original_path',
    'statut',
    'ai_summary',
    'ia_status',
    'ai_summary_read_at',
    'submitted_at',
])]
class Report extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_rapport' => 'date',
            'ai_summary_read_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Dispatcher, $this>
     */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(Dispatcher::class);
    }

    /**
     * @return HasMany<ReportEntry, $this>
     */
    public function reportEntries(): HasMany
    {
        return $this->hasMany(ReportEntry::class);
    }
}
