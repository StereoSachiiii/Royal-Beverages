<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\ImageService;
use App\Admin\Exceptions\ValidationException;

class ImageController extends BaseController
{
    private ImageService $service;

    public function __construct()
    {
        $this->service = new ImageService();
    }

    public function upload(array $post, array $files): array
    {
        return $this->handle(function () use ($post, $files) {
            if (empty($post['entity'])) {
                throw new ValidationException('Missing entity');
            }

            if (!isset($files['image'])) {
                throw new ValidationException('Missing image file');
            }

            $entity = (string) $post['entity'];

            $result = $this->service->upload($entity, $files['image']);

            return $this->success('Image uploaded', $result, 201);
        });
    }
}
