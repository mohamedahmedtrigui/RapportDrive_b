<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'telephone', 'ville'])]
class Driver extends Model
{
    /**
     * @return HasMany<ReportEntry, $this>
     */
    public function reportEntries(): HasMany
    {
        return $this->hasMany(ReportEntry::class);
    }
}
