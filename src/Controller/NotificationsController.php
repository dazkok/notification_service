<?php

namespace App\Controller;

use App\Dto\NotificationRequestDto;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class NotificationsController extends AbstractController
{
    #[Route('/notifications', name: 'notifications_send', methods: 'POST')]
    public function send(
        #[MapRequestPayload] NotificationRequestDto $dto,
        NotificationService                         $service
    ): JsonResponse
    {
        try {
            $service->process($dto);

            return $this->json([
                'status' => 'success',
                'message' => 'Notification sent successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
