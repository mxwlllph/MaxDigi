<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang dipicu ketika status transaksi diperbarui melalui webhook.
 *
 * @author Maxwell Alpha
 */
class TransactionStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Data transaksi yang diterima dari webhook.
     *
     * @var array
     */
    public array $transactionData;

    /**
     * Membuat instance event baru.
     *
     * @param array $transactionData Data yang diterima dari webhook DigiFlazz
     */
    public function __construct(array $transactionData)
    {
        $this->transactionData = $transactionData;
    }
}
