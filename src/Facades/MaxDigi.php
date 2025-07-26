<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array cekSaldo()
 * @method static array daftarHarga(string $sku_code = null)
 * @method static array deposit(int $amount, string $bank, string $owner_name)
 * @method static array transaksi(string $sku_code, string $customer_no, string $ref_id)
 *
 * @see \Mxwlllph\MaxDigi\Services\MaxDigiService
 * @author Maxwell Alpha
 */
class MaxDigi extends Facade
{
    /**
     * Mendapatkan nama komponen yang terdaftar.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'maxdigi';
    }
}
