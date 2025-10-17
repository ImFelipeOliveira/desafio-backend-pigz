<?php

namespace App\Infra\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Application\UseCase\Auth\LoginUseCase;
use App\Application\DTO\Auth\Credentials;
use App\Application\UseCase\Auth\RegisterUseCase;
use App\Application\DTO\Auth\Register;

final class AuthController extends AbstractController
{

    #[Route('/auth/login', name: 'app_auth', methods: ['POST'])]
    public function login(Request $request, LoginUseCase $loginUseCase): JsonResponse
    {
        try {
            $data = json_decode($request->getContent() ?: '{}', true);
            $credentials = new Credentials(
                $data['email'] ?? '',
                $data['password'] ?? ''
            );
            $result = $loginUseCase->execute($credentials);
            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/auth/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, RegisterUseCase $registerUseCase): JsonResponse
    {
        try {
            $data = json_decode($request->getContent() ?: '{}', true);
            $registerDto = new Register(
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['confirmPassword'] ?? '',
                $data['role'] ?? 'ROLE_USER'
            );
            $registerUseCase->execute($registerDto);
            return $this->json(['message' => 'User registered successfully.'], JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
