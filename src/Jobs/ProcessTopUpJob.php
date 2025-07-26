<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mxwlllph\MaxDigi\Facades\MaxDigi;
use Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException;
use Illuminate\Support\Facades\Log;

/**
 * Job untuk memproses permintaan transaksi top-up secara asinkron.
 *
 * @author Maxwell Alpha
 */
class ProcessTopUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal untuk job ini.
     * Jika gagal, akan dicoba lagi sebanyak 3 kali.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Waktu (dalam detik) untuk menunggu sebelum mencoba kembali job.
     * Akan dicoba lagi setelah 60, 120, 180 detik.
     *
     * @var int|array
     */
    public array $backoff = [60, 120, 180];

    /**
     * Membuat instance job baru.
     *
     * @param string $sku_code
     * @param string $customer_no
     * @param string $ref_id
     */
    public function __construct(
        public string $sku_code,
        public string $customer_no,
        public string $ref_id
    ) {}

    /**
     * Menjalankan job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Memanggil service untuk melakukan transaksi
            $response = MaxDigi::transaksi($this->sku_code, $this->customer_no, $this->ref_id);

            // Log response sukses untuk debugging
            Log::info('Transaksi berhasil diproses untuk ref_id: ' . $this->ref_id, $response);

        } catch (DigiFlazzApiException $e) {
            // Jika terjadi error API yang spesifik
            Log::error('Gagal memproses transaksi (DigiFlazz API Error) untuk ref_id: ' . $this->ref_id . ' | Pesan: ' . $e->getMessage());

            // Gagal kan job agar tidak diulang jika ini bukan error sementara.
            // Anda bisa menambahkan logika di sini, misal jika error karena saldo tidak cukup, tidak perlu retry.
            $this->fail($e);

        } catch (\Throwable $e) {
            // Tangkap semua error lainnya
            Log::critical('Error kritis saat proses job transaksi untuk ref_id: ' . $this->ref_id . ' | Pesan: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
