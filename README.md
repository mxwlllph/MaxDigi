# Pustaka Laravel MaxDigi (Wrapper untuk API DigiFlazz)

Pustaka ini menyediakan cara yang elegan dan modern untuk mengintegrasikan aplikasi Laravel Anda dengan API DigiFlazz. Dibangun dengan fitur-fitur canggih seperti proses Asinkron (Jobs), penanganan Webhook yang aman, dan arsitektur berbasis Event.

**Oleh: Maxwell**

## Fitur Unggulan

- ✅ **API Bersih**: Akses mudah dengan Facade (`MaxDigi::cekSaldo()`).
- 🚀 **Transaksi Asinkron**: Transaksi diproses di latar belakang menggunakan Laravel Jobs & Queues, membuat aplikasi Anda super cepat.
- 🛡️ **Webhook Aman**: Dilengkapi dengan verifikasi *signature* untuk memastikan permintaan webhook benar-benar dari DigiFlazz.
- 📦 **Penanganan Error**: Sistem tangguh dengan *custom exception* dan *job retry* otomatis.
- 🔔 **Berbasis Event**: Logika terpisah dengan `TransactionStatusUpdated` event.

## Instalasi

```bash
composer require mxwlllph/maxdigi
```

## Konfigurasi

1.  **Publikasikan File Konfigurasi**

    Jalankan perintah ini untuk mempublikasikan file `config/maxdigi.php`:

    ```bash
    php artisan vendor:publish --provider="Mxwlllph\MaxDigi\MaxDigiServiceProvider" --tag="maxdigi-config"
    ```

2.  **Atur Variabel Environment (.env)**

    Buka file `.env` Anda dan tambahkan kredensial serta kunci rahasia webhook.

    ```env
    DIGIFLAZZ_USERNAME=username_anda
    DIGIFLAZZ_API_KEY=kunci_api_produksi_anda
    DIGIFLAZZ_WEBHOOK_SECRET=kunci_rahasia_webhook_dari_digiflazz
    ```

    > **Penting**: Pastikan `DIGIFLAZZ_WEBHOOK_SECRET` sama persis dengan yang Anda atur di dashboard developer DigiFlazz.

## Cara Penggunaan

### 1. Cek Saldo

```php
use Mxwlllph\MaxDigi\Facades\MaxDigi;
use Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException;

try {
    $saldo = MaxDigi::cekSaldo();
    dump($saldo['deposit']);
} catch (DigiFlazzApiException $e) {
    // Tangani error, misal: log, tampilkan pesan ke user
    echo "Gagal cek saldo: " . $e->getMessage();
}
```

### 2. Memulai Transaksi (Asinkron)

Sangat disarankan untuk melakukan validasi input menggunakan `FormRequest` sebelum mengirim job.

```php
use Mxwlllph\MaxDigi\Jobs\ProcessTopUpJob;

// Data dari request pengguna (setelah divalidasi)
$sku_code = 'XLD10';
$customer_no = '087812345678';
$ref_id = 'INV-' . uniqid(); // Pastikan ref_id unik untuk setiap transaksi

// Kirim pekerjaan ke queue untuk diproses di latar belakang
ProcessTopUpJob::dispatch($sku_code, $customer_no, $ref_id);

// Beri respons cepat ke pengguna
return response()->json(['message' => 'Permintaan Anda sedang diproses.']);
```

### 3. Menangani Status Transaksi (via Webhook)

Pustaka ini secara otomatis akan menerima webhook di route `api/maxdigi/webhook`. Ketika status transaksi berubah, event `TransactionStatusUpdated` akan dipicu.

Buat sebuah *Listener* untuk menangani event ini:

```bash
php artisan make:listener UpdateTransactionStatus
```

Buka `app/Listeners/UpdateTransactionStatus.php` dan implementasikan logikanya:

```php
namespace App\Listeners;

use Mxwlllph\MaxDigi\Events\TransactionStatusUpdated;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction; // Contoh model transaksi Anda

class UpdateTransactionStatus
{
    public function handle(TransactionStatusUpdated $event): void
    {
        $data = $event->transactionData;

        Log::info('Webhook diterima:', $data);

        // Contoh: Cari transaksi di database Anda berdasarkan ref_id
        $transaction = Transaction::where('ref_id', $data['ref_id'])->first();

        if ($transaction) {
            // Update status transaksi
            $transaction->status = strtolower($data['status']); // misal: 'Sukses', 'Gagal'
            $transaction->message = $data['message'];
            $transaction->save();
        }
    }
}
```

Jangan lupa daftarkan *Listener* Anda di `app/Providers/EventServiceProvider.php`.

## Lisensi

Pustaka ini berlisensi MIT. Silakan lihat File Lisensi untuk informasi lebih lanjut.
