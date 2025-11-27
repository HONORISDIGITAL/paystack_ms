<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event',
        'timestamp',
        'transaction_id',
        'reference',
        'amount',
        'charged_amount',
        'requested_amount',
        'app_fee',
        'merchant_fee',
        'currency',
        'status',
        'payment_type',
        'channel',
        'payment_page',
        'price',
        'processor_response',
        'gateway_response',
        'domain',
        'email',
        'card_issuer',
        'card_type',
        'card_country',
        'log',
        'meta_data',
        'webhook_id',
        'signature_valid',
        'paid_at',
        'flw_created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charged_amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'app_fee' => 'decimal:2',
        'merchant_fee' => 'decimal:2',
        'price' => 'decimal:2',
        'log' => 'array',
        'meta_data' => 'array',
        'signature_valid' => 'boolean',
        'timestamp' => 'datetime',
        'paid_at' => 'datetime',
        'flw_created_at' => 'datetime',
    ];

    /**
     * Scope to get successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope to get payments by customer email
     */
    public function scopeByCustomer($query, $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful()
    {
        return $this->status === 'successful';
    }

    /**
     * Check if payment is card payment
     */
    public function isCardPayment()
    {
        return $this->payment_type === 'card';
    }
}
