<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'pending_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
    ];

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the owner (Vendor or system)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Credit the wallet
     */
    public function credit(float $amount, string $reference, string $description, array $metadata = []): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'reference' => $reference,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add to pending balance (not yet available)
     */
    public function addPending(float $amount, string $reference, string $description, array $metadata = []): WalletTransaction
    {
        $balanceBefore = $this->pending_balance;
        $this->pending_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'pending_credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->pending_balance,
            'reference' => $reference,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Convert pending to available balance
     */
    public function confirmPending(float $amount): void
    {
        $this->pending_balance -= $amount;
        $this->balance += $amount;
        $this->save();
    }

    /**
     * Debit the wallet
     */
    public function debit(float $amount, string $reference, string $description, array $metadata = []): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'reference' => $reference,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get or create wallet for an owner
     */
    public static function getOrCreate(string $ownerType, ?int $ownerId = null): Wallet
    {
        return static::firstOrCreate([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ], [
            'balance' => 0,
            'pending_balance' => 0,
        ]);
    }
}
