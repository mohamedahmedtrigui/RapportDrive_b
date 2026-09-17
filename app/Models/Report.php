<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['dispatcher_id', 'ville', 'date_rapport', 'fichier_original_path', 'statut'])]
class Report extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_rapport' => 'date',
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
