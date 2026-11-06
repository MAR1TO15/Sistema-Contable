<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Database\Factories\FiscalPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'name', 'start_date', 'end_date', 'status', 'closed_by', 'closed_at'])]
class FiscalPeriod extends Model
{
    /** @use HasFactory<FiscalPeriodFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'abierto',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PeriodStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === PeriodStatus::Abierto;
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Limit the query to periods that share at least one day with the
     * given range (both ends inclusive).
     */
    #[Scope]
    protected function overlapping(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}
