<?php

declare(strict_types=1);

namespace App\Infra\Security;

use App\Application\Security\JwtManagerInterface;
use App\Infra\Persistence\Doctrine\Entity\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
  public function __construct(
    private JwtManagerInterface $jwtManager,
    private EntityManagerInterface $entityManager
  ) {}

  public function supports(Request $request): ?bool
  {
    return $request->headers->has('Authorization');
  }

  public function authenticate(Request $request): Passport
  {
    $authHeader = $request->headers->get('Authorization');

    if (null === $authHeader) {
      throw new CustomUserMessageAuthenticationException('No API token provided');
    }

    if (!str_starts_with($authHeader, 'Bearer ')) {
      throw new CustomUserMessageAuthenticationException('Invalid authorization header format');
    }

    $token = substr($authHeader, 7);

    try {
      $payload = $this->jwtManager->getPayload($token);
      $email = $payload['sub'] ?? null;

      if (!$email) {
        throw new CustomUserMessageAuthenticationException('Invalid token payload');
      }

      return new SelfValidatingPassport(
        new UserBadge($email, function ($userIdentifier) {
          $user = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => $userIdentifier]);

          if (!$user) {
            throw new CustomUserMessageAuthenticationException('User not found');
          }

          return $user;
        })
      );
    } catch (\Exception $e) {
      throw new CustomUserMessageAuthenticationException('Invalid or expired token: ' . $e->getMessage());
    }
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
  {
    return null;
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
  {
    return new JsonResponse([
      'error' => $exception->getMessageKey()
    ], Response::HTTP_UNAUTHORIZED);
  }
}
