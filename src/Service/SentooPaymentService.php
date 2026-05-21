<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentooPaymentService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiBaseUrl,
        private string $merchantId,
        private string $secret,
        private string $currency,
        private string $returnUrl,
    ) {
    }

    public function getReturnUrl(Reservation $reservation, string $defaultReturnUrl): string
    {
        if (!$this->returnUrl) {
            return $defaultReturnUrl;
        }

        return str_replace('{id}', (string) $reservation->getId(), $this->returnUrl);
    }

    public function createTransaction(Reservation $reservation, float $amount, string $returnUrl): array
    {
        $this->checkSettings();

        $response = $this->httpClient->request('POST', rtrim($this->apiBaseUrl, '/') . '/v1/payment/new', [
            'headers' => [
                'X-SENTOO-SECRET' => $this->secret,
            ],
            'body' => [
                'sentoo_merchant' => $this->merchantId,
                'sentoo_amount' => (int) round($amount * 100),
                'sentoo_description' => substr('Reservation #' . $reservation->getId(), 0, 50),
                'sentoo_currency' => $this->currency,
                'sentoo_return_url' => $returnUrl,
                'sentoo_customer' => (string) $reservation->getCustomer()?->getEmail(),
            ],
        ]);

        $data = $response->toArray(false);

        if (!isset($data['success'])) {
            throw new \RuntimeException($data['error']['message'] ?? 'Could not create Sentoo payment.');
        }

        return [
            'transactionId' => $data['success']['message'],
            'paymentUrl' => $data['success']['data']['url'],
            'qrCodeUrl' => $data['success']['data']['qr_code'],
        ];
    }

    public function fetchTransactionStatus(string $transactionId): array
    {
        $this->checkSettings();

        $response = $this->httpClient->request('GET', sprintf(
            '%s/v1/payment/status/%s/%s',
            rtrim($this->apiBaseUrl, '/'),
            $this->merchantId,
            $transactionId
        ), [
            'headers' => [
                'X-SENTOO-SECRET' => $this->secret,
            ],
        ]);

        $data = $response->toArray(false);

        if (!isset($data['success'])) {
            throw new \RuntimeException($data['error']['message'] ?? 'Could not fetch Sentoo payment status.');
        }

        return [
            'status' => $data['success']['message'],
            'message' => $this->findProcessorMessage($data),
        ];
    }

    private function findProcessorMessage(array $data): ?string
    {
        $responses = $data['success']['data']['responses'] ?? [];
        $lastResponse = end($responses);

        if (!is_array($lastResponse)) {
            return null;
        }

        return $lastResponse['message'] ?? null;
    }

    private function checkSettings(): void
    {
        if (!$this->merchantId || !$this->secret || !$this->currency) {
            throw new \RuntimeException('Sentoo payment settings are missing.');
        }
    }
}
