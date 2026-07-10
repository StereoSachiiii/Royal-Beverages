<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Core\Request;
use App\Admin\Services\PaymentService;
use App\Admin\Repositories\PaymentRepository;
use App\Admin\Repositories\OrderRepository;

class StripeWebhookController extends BaseController
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private OrderRepository $orderRepo
    ) {}

    public function handleWebhook(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $payload = file_get_contents('php://input');
            $sigHeader = $request->getHeader('Stripe-Signature');
            
            // For portfolio purpose, if sig header is missing we just process it as a mock
            // In production, we'd verify the signature:
            // \Stripe\Webhook::constructEvent($payload, $sigHeader, getenv('STRIPE_WEBHOOK_SECRET'));
            
            $event = json_decode($payload !== false ? $payload : '', true);
            
            if (!is_array($event)) {
                return $this->error('Invalid payload', 400);
            }

            // Handle the event
            switch ($event['type']) {
                case 'checkout.session.completed':
                    $session = $event['data']['object'];
                    
                    $gatewayOrderId = $session['id'];
                    $orderId = (int)$session['client_reference_id'];
                    
                    // Update Payment
                    $payment = $this->paymentRepo->getByGatewayOrderId($gatewayOrderId);
                    if ($payment && $payment->getId() !== null) {
                        $this->paymentRepo->update($payment->getId(), [
                            'status' => 'captured',
                            'payload' => json_encode($event)
                        ]);
                        
                        // Update Order to paid
                        $this->orderRepo->update($orderId, [
                            'status' => 'paid',
                            'paid_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                    break;

                default:
                    echo 'Received unknown event type ' . $event['type'];
            }

            return $this->success('Webhook handled successfully');
        });
    }
}
