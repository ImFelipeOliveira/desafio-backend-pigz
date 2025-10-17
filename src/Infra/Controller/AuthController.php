<?php

namespace App\Infra\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Application\UseCase\Auth\LoginUseCase;
use App\Application\DTO\Auth\Credentials;

final class AuthController extends AbstractController
{

    #[Route('/auth/login', name: 'app_auth', methods: ['POST'])]
    public function login(Request $request, LoginUseCase $loginUseCase): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);

        $credentials = new Credentials(
            $data['email'] ?? '',
            $data['password'] ?? ''
        );

        $result = $loginUseCase->execute($credentials);

        return $this->json($result);
    }
}
