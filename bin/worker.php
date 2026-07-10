<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/bootstrap.php';

use App\DIContainer\Container;
use App\Admin\API\ApiServiceProvider;
use App\Admin\Repositories\JobRepository;
use App\Admin\Services\OrderService;

echo "Starting Background Job Worker...\n";

$container = new Container();
$provider = new ApiServiceProvider($container);
$provider->register();

/** @var JobRepository $jobRepository */
$jobRepository = $container->get(JobRepository::class);

/** @var OrderService $orderService */
$orderService = $container->get(OrderService::class);

while (true) {
    try {
        $job = $jobRepository->claimNext();
        if ($job) {
            echo "Processing Job: {$job->id} [{$job->jobType}]\n";
            
            // Route the job to the correct handler
            switch ($job->jobType) {
                case 'order_confirmation':
                    // e.g. $orderService->sendConfirmationEmail($job->payload['order_id']);
                    echo "-> Sent order confirmation for Order ID: " . ($job->payload['order_id'] ?? 'Unknown') . "\n";
                    break;
                
                case 'low_stock_alert':
                    echo "-> Low stock alert processed.\n";
                    break;
                
                default:
                    echo "-> Unknown job type: {$job->jobType}\n";
                    break;
            }

            $jobRepository->complete($job->id);
            echo "Completed Job: {$job->id}\n";
        } else {
            // No pending jobs, sleep for 2 seconds
            sleep(2);
        }
    } catch (\Throwable $e) {
        echo "Error processing job: " . $e->getMessage() . "\n";
        // Let it sleep before retrying to avoid CPU spam
        sleep(5);
    }
}
