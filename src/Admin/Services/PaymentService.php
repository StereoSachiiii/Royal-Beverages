<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\PaymentRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

use App\DTO\Requests\CreatePaymentRequest;
use App\DTO\Requests\UpdatePaymentRequest;
use App\DTO\DTOException;

class PaymentService
{
    private string $stripeSecretKey;

    public function __construct(
        private PaymentRepository $repo,
        private ?\App\Admin\Repositories\OrderRepository $orderRepo = null
    ) {
        $this->stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_dummy';
    }

    public function create(array $data): array
    {
        try {
            $dto = CreatePaymentRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $payment = $this->repo->create($dto->toArray());
        return $payment->toArray();
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $payments = $this->repo->getAll($limit, $offset);
        return array_map(fn($p) => $p->toArray(), $payments);
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $payments = $this->repo->search($query, $limit, $offset);
        return array_map(fn($p) => $p->toArray(), $payments);
    }

    public function getById(int $id): array
    {
        $payment = $this->repo->getById($id);
        if (!$payment) {
            throw new NotFoundException('Payment not found');
        }
        return $payment->toArray();
    }

    public function getByOrder(int $orderId): array
    {
        $payments = $this->repo->getByOrder($orderId);
        return array_map(fn($p) => $p->toArray(), $payments);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function update(int $id, array $data): array
    {
        try {
            $dto = UpdatePaymentRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $updated = $this->repo->update($id, $dto->toChangeset());
        if (!$updated) {
            throw new NotFoundException('Payment not found');
        }

        return $updated->toArray();
    }

    public function delete(int $id): void
    {
        $deleted = $this->repo->delete($id);
        if (!$deleted) {
            throw new NotFoundException('Payment not found');
        }
    }

    public function hardDelete(int $id): void
    {
        $deleted = $this->repo->hardDelete($id);
        if (!$deleted) {
            throw new NotFoundException('Payment not found');
        }
    }

    public function createStripeCheckoutSession(int $orderId, string $successUrl, string $cancelUrl): array
    {
        if (!$this->orderRepo) {
            throw new \RuntimeException("OrderRepository not injected into PaymentService");
        }

        $order = $this->orderRepo->getDetailedOrderById($orderId);
        if (!$order) {
            throw new NotFoundException('Order not found');
        }

        $lineItems = [];
        $items = is_string($order['items']) ? json_decode($order['items'], true) : $order['items'];
        
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'lkr',
                    'product_data' => [
                        'name' => $item['product_name'] ?? 'Unknown Product',
                    ],
                    'unit_amount' => $item['price_cents'],
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $postFields = http_build_query([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string)$order['id'],
            'line_items' => $lineItems
        ]);

        // Fix http_build_query nested array encoding for Stripe
        $postFields = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $postFields);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$this->stripeSecretKey}\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'ignore_errors' => true
            ]
        ]);

        $response = file_get_contents('https://api.stripe.com/v1/checkout/sessions', false, $context);
        $result = json_decode($response !== false ? $response : '', true);

        if (!isset($result['id'])) {
            throw new ValidationException('Failed to create Stripe Checkout Session', $result);
        }

        // Record pending payment
        $this->repo->create([
            'order_id' => $orderId,
            'amount_cents' => $order['total_cents'],
            'currency' => 'LKR',
            'gateway' => 'stripe',
            'gateway_order_id' => $result['id'],
            'status' => 'pending'
        ]);

        return $result;
    }
}
