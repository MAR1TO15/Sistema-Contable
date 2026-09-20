<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['firm_id', 'name', 'tax_id', 'is_active'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Firm, $this>
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Limit the query to the clients a given user is allowed to see:
     * an admin_firma sees every client of their firm, a contador only
     * the clients explicitly assigned to them.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->where('firm_id', $user->firm_id)
            ->when(
                $user->role === UserRole::Contador,
                fn (Builder $query) => $query->whereHas(
                    'users',
                    fn (Builder $query) => $query->whereKey($user->id)
                ),
            );
    }
}
