<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Mxwlllph\MaxDigi\Events\TransactionStatusUpdated;

/**
 * Controller untuk menangani callback (webhook) dari DigiFlazz.
 *
 * @author Maxwell Alpha
 */
class WebhookController extends Controller
{
    /**
     * Menangani permintaan webhook yang masuk.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verifikasi Signature untuk keamanan
        $secret = config('maxdigi.webhook_secret');
        $signature = $request->header('X-Hub-Signature');

        if (!$secret || !$signature || !$this->isValidSignature($request->getContent(), $signature, $secret)) {
            // Jika signature tidak valid, tolak permintaan.
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        // 2. Proses data dari webhook
        $data = $request->input('data');

        if (empty($data)) {
            return response()->json(['message' => 'No data received.'], 400);
        }

        // 3. Memicu Event
        // Dengan memicu event, kita memisahkan logika.
        // Aplikasi utama bisa membuat Listener untuk event ini.
        event(new TransactionStatusUpdated($data));

        // 4. Kirim respons sukses
        return response()->json(['message' => 'Webhook received successfully.']);
    }

    /**
     * Memvalidasi signature dari permintaan webhook.
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    private function isValidSignature(string $payload, string $signature, string $secret): bool
    {
        // Signature dari DigiFlazz adalah "sha1=" + hash_hmac
        $expectedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        // Gunakan hash_equals untuk perbandingan yang aman (mencegah timing attack)
        return hash_equals($expectedSignature, $signature);
    }
}
