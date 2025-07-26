# Pustaka MaxDigi untuk Integrasi API DigiFlazz pada Laravel

## Pendahuluan

Pustaka MaxDigi adalah sebuah *wrapper* perangkat lunak yang dirancang untuk menyediakan abstraksi yang elegan dan efisien untuk berinteraksi dengan API DigiFlazz di dalam ekosistem Laravel. Tujuan utamanya adalah untuk menyederhanakan proses pengembangan dengan menyediakan fitur-fitur modern seperti pemrosesan tugas secara asinkron (*asynchronous jobs*), penanganan *webhook* yang aman, dan arsitektur berbasis *event*.

Dokumen ini berfungsi sebagai panduan komprehensif untuk instalasi, konfigurasi, dan implementasi pustaka MaxDigi.

**Kontributor Utama:** Maxwell Alpha

---

## Arsitektur dan Alur Kerja

Untuk memanfaatkan pustaka ini secara optimal, penting untuk memahami dua alur kerja utamanya:

1.  **Alur Perintah Keluar (Contoh: Inisiasi Transaksi)**
    Alur ini terjadi ketika aplikasi Anda mengirimkan perintah ke API DigiFlazz.
    * **Aplikasi**: *Controller* pada aplikasi Anda menerima permintaan untuk membuat transaksi.
    * **Dispatch Job**: Aplikasi membuat catatan transaksi awal di basis data lokal dengan status `pending`, lalu mengirimkan `ProcessTopUpJob` ke dalam sistem antrian (*queue*).
    * **Queue Worker**: Sebuah *worker* di latar belakang mengambil dan mengeksekusi *job* tersebut.
    * **Eksekusi API**: *Job* memanggil `MaxDigiService` yang kemudian berkomunikasi dengan API DigiFlazz untuk memproses transaksi.

    Keunggulan alur ini adalah aplikasi Anda dapat memberikan respons kepada pengguna secara instan tanpa harus menunggu penyelesaian panggilan API eksternal.

2.  **Alur Respons Masuk (Penanganan Webhook)**
    Alur ini terjadi ketika DigiFlazz mengirimkan pembaruan status transaksi ke aplikasi Anda.
    * **DigiFlazz**: Mengirimkan permintaan `POST` (*webhook*) ke *endpoint* yang telah Anda konfigurasikan.
    * **Endpoint Pustaka**: Pustaka MaxDigi menerima permintaan ini melalui *route* yang telah disediakan (`api/maxdigi/webhook`).
    * **Verifikasi & Event**: `WebhookController` di dalam pustaka memverifikasi keaslian permintaan menggunakan *signature* rahasia, lalu memicu sebuah *event* bernama `TransactionStatusUpdated` yang membawa data status transaksi.
    * **Aplikasi (Listener)**: Sebuah *Listener* pada aplikasi Anda, yang telah didaftarkan untuk "mendengarkan" *event* tersebut, akan dieksekusi untuk memperbarui catatan transaksi di basis data Anda.

---

## Instalasi dan Konfigurasi

Ikuti langkah-langkah berikut untuk mengintegrasikan pustaka MaxDigi ke dalam proyek Laravel Anda.

### Langkah 1: Instalasi via Composer
Buka terminal pada direktori root proyek Anda dan jalankan perintah Composer berikut:
```bash
composer require mxwlllph/maxdigi
```

### Langkah 2: Publikasi File Konfigurasi
Pustaka ini menyertakan sebuah file konfigurasi yang dapat Anda modifikasi. Untuk mempublikasikannya ke direktori `config` aplikasi Anda, jalankan perintah Artisan:
```bash
php artisan vendor:publish --provider="Mxwlllph\MaxDigi\MaxDigiServiceProvider" --tag="maxdigi-config"
```
Perintah di atas akan menghasilkan file `config/maxdigi.php`.

### Langkah 3: Konfigurasi Variabel Lingkungan (.env)
Tambahkan variabel berikut pada file `.env` Anda dan isi nilainya sesuai dengan kredensial dari akun DigiFlazz Anda.

```env
DIGIFLAZZ_USERNAME=your_digiflazz_username
DIGIFLAZZ_API_KEY=your_production_api_key
DIGIFLAZZ_WEBHOOK_SECRET=your_webhook_secret_key
```

* `DIGIFLAZZ_USERNAME`: Merupakan username akun DigiFlazz.
* `DIGIFLAZZ_API_KEY`: Merupakan kunci API mode produksi.
* `DIGIFLAZZ_WEBHOOK_SECRET`: Kunci rahasia yang digunakan untuk validasi *signature webhook*. Nilai ini harus identik dengan yang terkonfigurasi pada panel DigiFlazz Anda untuk memastikan keamanan.

### Langkah 4: Menjalankan Proses Antrian (Queue Worker)
Karena proses transaksi dijalankan secara asinkron, pastikan proses *worker* untuk antrian Laravel Anda berjalan. Untuk lingkungan pengembangan, Anda dapat menggunakan perintah:
```bash
php artisan queue:work
```
Pada lingkungan produksi, disarankan untuk mengonfigurasi Supervisor untuk menjaga agar proses *worker* tetap berjalan secara persisten.

---

## Panduan Implementasi

Berikut adalah contoh implementasi praktis dari fitur-fitur utama pustaka ini.

### 1. Inisiasi Transaksi

Contoh berikut mendemonstrasikan cara membuat transaksi baru dari sebuah `Controller`.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transaction; // Asumsi model Transaction telah dibuat
use Mxwlllph\MaxDigi\Jobs\ProcessTopUpJob;

class OrderController extends Controller
{
    /**
     * Menyimpan dan memproses permintaan transaksi baru.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Fase 1: Validasi Input
        // Pastikan data yang dikirim oleh pengguna sesuai dengan format yang diharapkan.
        $validatedData = $request->validate([
            'sku_code'    => 'required|string|max:50',
            'customer_no' => 'required|string|max:20',
        ]);

        // Fase 2: Pembuatan Catatan Lokal
        // Buat entri transaksi pada basis data lokal sebelum mengirim ke API.
        // Ini penting untuk pelacakan dan rekonsiliasi.
        $transaction = Transaction::create([
            'reference_id' => 'ORD-' . strtoupper(Str::random(12)),
            'sku_code'     => $validatedData['sku_code'],
            'customer_no'  => $validatedData['customer_no'],
            'status'       => 'pending',
            // 'user_id'      => auth()->id(), // Opsional: kaitkan dengan pengguna
            // 'price'        => $this->getPriceForSku($validatedData['sku_code']), // Opsional
        ]);

        // Fase 3: Dispatch Job
        // Tugas dikirim ke antrian untuk diproses di latar belakang.
        ProcessTopUpJob::dispatch(
            $transaction->sku_code,
            $transaction->customer_no,
            $transaction->reference_id // Gunakan ID unik dari basis data Anda sebagai `ref_id`.
        );

        // Fase 4: Respons Instan
        // Kembalikan respons ke pengguna bahwa permintaan sedang diproses.
        return response()->json([
            'message' => 'Permintaan transaksi telah diterima dan akan segera diproses.',
            'data'    => $transaction,
        ], 202); // HTTP 202 Accepted
    }
}
```

### 2. Penanganan Respons Transaksi (Webhook dan Listener)

Ini adalah langkah krusial untuk menerima pembaruan status transaksi secara otomatis.

#### Langkah 2.1: Pembuatan Listener
Buat sebuah kelas `Listener` yang akan dieksekusi setiap kali event `TransactionStatusUpdated` dipicu oleh pustaka. Jalankan perintah Artisan berikut:

```bash
php artisan make:listener UpdateTransactionRecord --event=TransactionStatusUpdated
```
Perintah ini akan membuat file baru di `app/Listeners/UpdateTransactionRecord.php` dan secara otomatis mengaitkannya pada `app/Providers/EventServiceProvider.php`.

#### Langkah 2.2: Implementasi Logika Listener
Buka file `app/Listeners/UpdateTransactionRecord.php` dan definisikan logika untuk memperbarui basis data Anda berdasarkan data dari *webhook*.

```php
<?php

namespace App\Listeners;

use Mxwlllph\MaxDigi\Events\TransactionStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Transaction; // Model Transaction lokal Anda
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateTransactionRecord
 * Mengimplementasikan ShouldQueue agar listener berjalan di antrian (asinkron),
 * mencegah timeout pada saat menerima webhook.
 */
class UpdateTransactionRecord implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Menangani event pembaruan status transaksi.
     *
     * @param \Mxwlllph\MaxDigi\Events\TransactionStatusUpdated $event
     * @return void
     */
    public function handle(TransactionStatusUpdated $event): void
    {
        $webhookData = $event->transactionData;

        // Log data yang diterima untuk keperluan diagnostik.
        Log::channel('webhooks')->info('DigiFlazz Webhook Received:', $webhookData);

        // Cari transaksi yang sesuai di basis data menggunakan `ref_id`.
        $transaction = Transaction::where('reference_id', $webhookData['ref_id'])->first();

        if ($transaction) {
            // Perbarui status dan pesan berdasarkan data dari webhook.
            $transaction->status = strtolower($webhookData['status']); // Contoh: 'sukses', 'gagal'
            $transaction->message = $webhookData['message'];
            $transaction->save();

            // Di sini, Anda dapat menambahkan logika bisnis lain, seperti:
            // - Mengirim notifikasi email/push kepada pengguna.
            // - Memicu event lain jika transaksi sukses.
        } else {
            // Catat jika `ref_id` dari webhook tidak ditemukan di sistem Anda.
            Log::channel('webhooks')->warning('Webhook received for an unknown reference_id.', $webhookData);
        }
    }
}
```

### 3. Penggunaan Fungsi Sinkron (Contoh: Cek Saldo)

Untuk fungsi yang tidak memerlukan pemrosesan latar belakang, Anda dapat memanggilnya secara langsung. Selalu gunakan blok `try-catch` untuk menangani potensi kegagalan API.

```php
use Mxwlllph\MaxDigi\Facades\MaxDigi;
use Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException;
use Illuminate\Support\Facades\Log;

class BalanceController extends Controller
{
    public function show()
    {
        try {
            $response = MaxDigi::cekSaldo();
            $balance = $response['deposit'];

            return response()->json(['current_balance' => $balance]);

        } catch (DigiFlazzApiException $e) {
            // Tangani error spesifik dari API.
            Log::error('Gagal mengambil saldo DigiFlazz: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung ke layanan.'], 503); // HTTP 503 Service Unavailable
        }
    }
}
```

## Lisensi

Pustaka ini berlisensi MIT. Silakan lihat File Lisensi untuk informasi lebih lanjut.
