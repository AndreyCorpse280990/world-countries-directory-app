<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class StatusController extends AbstractController
{
    #[Route('', name: 'api_status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        return $this->json([
            'status' => 'server is running',
            'host' => $request->getHost(),
            'protocol' => $request->getScheme(),
        ]);
    }

    #[Route('/ping', name: 'api_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'status' => 'pong',
        ]);
    }
}