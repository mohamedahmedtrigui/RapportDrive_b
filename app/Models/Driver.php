<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'telephone', 'ville'])]
class Driver extends Model
{
    use HasFactory;

    private const SEVERITY_PENALTY = [
        'haute' => 15,
        'moyenne' => 5,
        'faible' => 1,
    ];

    private const MAX_NOTES = 10;

    /**
     * @return HasMany<ReportEntry, $this>
     */
    public function reportEntries(): HasMany
    {
        return $this->hasMany(ReportEntry::class);
    }

    /**
     * Apply the outcome of an AI-analyzed entry to this driver's reputation
     * score and running notes. Not client-fillable by design — this is only
     * ever called internally from the analysis pipeline.
     */
    public function applyIncident(string $categorie, string $severite, string $texteNormalise): void
    {
        $penalty = self::SEVERITY_PENALTY[$severite] ?? 0;
        $this->score = max(0, min(100, $this->score - $penalty));

        $notes = $this->ai_notes ? preg_split('/\n/', $this->ai_notes) : [];
        array_unshift($notes, sprintf('[%s] %s — %s', $severite, $categorie, $texteNormalise));
        $this->ai_notes = implode("\n", array_slice($notes, 0, self::MAX_NOTES));

        $this->save();
    }
}
