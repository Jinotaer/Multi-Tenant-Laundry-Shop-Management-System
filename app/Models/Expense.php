<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * Expense category labels.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'supplies' => 'Supplies & Materials',
            'utilities' => 'Utilities',
            'labor' => 'Labor',
            'equipment' => 'Equipment',
            'other' => 'Other',
        ];
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? ucwords(str_replace('_', ' ', $this->category));
    }
}
