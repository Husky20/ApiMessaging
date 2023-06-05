<?php

namespace App\Controller;

use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MessageApiController extends AbstractController
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    #[Route('/api/messages/save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $notification = $this->messageService->saveMessages($data);

            return $this->json(["success: " => $notification]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/messages/list', methods: ['GET'])]
    public function list(?string $sortBy = null, ?string $sortOrder = 'asc'): JsonResponse
    {
        try {
            $messages = $this->messageService->getMessages($sortBy, $sortOrder);

            return $this->json($messages);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/messages/{uuid}', methods: ['GET'])]
    public function get(string $uuid): JsonResponse
    {
        try {
            $message = $this->messageService->findMessage($uuid);

            if (!$message) {
                return $this->json(['error' => 'Message not found.'], 404);
            }

            return $this->json($message);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }


}
