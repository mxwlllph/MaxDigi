<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException;

/**
 * Service class untuk berinteraksi dengan DigiFlazz API.
 *
 * @author Maxwell Alpha
 */
class MaxDigiService
{
    /**
     * Klien HTTP untuk melakukan permintaan.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected PendingRequest $client;

    /**
     * Base URL untuk API DigiFlazz.
     *
     * @var string
     */
    protected string $baseUrl = 'https://api.digiflazz.com/v1/';

    /**
     * Membuat instance service baru.
     *
     * @param string|null $username
     * @param string|null $apiKey
     */
    public function __construct(
        protected ?string $username,
        protected ?string $apiKey
    ) {
        if (!$this->username || !$this->apiKey) {
            throw new DigiFlazzApiException('Username atau API Key DigiFlazz tidak boleh kosong.');
        }

        $this->client = Http::baseUrl($this->baseUrl)->acceptJson();
    }

    /**
     * Membuat signature untuk permintaan API.
     *
     * @return string
     */
    private function createSignature(string $refId): string
    {
        return md5($this->username . $this->apiKey . $refId);
    }

    /**
     * Mengirim permintaan ke API dan menangani respons.
     *
     * @param string $endpoint
     * @param array $payload
     * @return array
     * @throws \Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException
     */
    private function sendRequest(string $endpoint, array $payload): array
    {
        try {
            $response = $this->client->post($endpoint, $payload);

            // Lempar exception jika status bukan 2xx (sukses)
            $response->throw();

            // Mengambil data dari 'data' key di dalam respons
            $data = $response->json('data');

            if (is_null($data)) {
                 throw new DigiFlazzApiException('Respons API tidak valid atau tidak berisi key "data".');
            }

            return $data;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Tangkap kegagalan koneksi atau status error 4xx/5xx
            $message = $e->response?->json('data.message') ?? $e->getMessage();
            throw new DigiFlazzApiException('Gagal terhubung ke API DigiFlazz: ' . $message);
        } catch (\Throwable $th) {
            // Tangkap semua error lainnya
            throw new DigiFlazzApiException('Terjadi error tak terduga: ' . $th->getMessage());
        }
    }

    /**
     * Cek saldo akun DigiFlazz.
     *
     * @return array
     * @throws \Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException
     */
    public function cekSaldo(): array
    {
        $payload = [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => md5($this->username . $this->apiKey . 'depo'),
        ];

        return $this->sendRequest('cek-saldo', $payload);
    }

    /**
     * Mengambil daftar harga produk.
     *
     * @param string|null $sku_code
     * @return array
     * @throws \Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException
     */
    public function daftarHarga(string $sku_code = null): array
    {
        $payload = [
            'cmd' => 'prepaid',
            'username' => $this->username,
            'sign' => md5($this->username . $this->apiKey . 'pricelist'),
        ];
        
        if ($sku_code) {
            $payload['code'] = $sku_code;
        }

        return $this->sendRequest('price-list', $payload);
    }
    
    /**
     * Melakukan top-up atau transaksi baru.
     *
     * @param string $sku_code
     * @param string $customer_no
     * @param string $ref_id
     * @return array
     * @throws \Mxwlllph\MaxDigi\Exceptions\DigiFlazzApiException
     */
    public function transaksi(string $sku_code, string $customer_no, string $ref_id): array
    {
        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $sku_code,
            'customer_no' => $customer_no,
            'ref_id' => $ref_id,
            'testing' => true, // Ganti ke false untuk produksi
            'sign' => $this->createSignature($ref_id),
        ];

        return $this->sendRequest('transaction', $payload);
    }
}
