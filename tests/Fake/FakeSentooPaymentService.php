<?php

namespace App\Tests\Fake;

use App\Entity\Reservation;
use App\Service\SentooPaymentService;

class FakeSentooPaymentService extends SentooPaymentService
{
    public function __construct()
    {
    }

    public function getReturnUrl(Reservation $reservation, string $defaultReturnUrl): string
    {
        return $defaultReturnUrl;
    }

    public function createTransaction(Reservation $reservation, float $amount, string $returnUrl): array
    {
        return [
            'transactionId' => 'test-transaction-' . $reservation->getId(),
            'paymentUrl' => 'https://pay.test/checkout',
            'qrCodeUrl' => 'https://pay.test/qr-code.png',
        ];
    }

    public function fetchTransactionStatus(string $transactionId): array
    {
        return [
            'status' => Reservation::PAYMENT_SUCCESS,
            'message' => 'Test payment success.',
        ];
    }
}
