<?php

declare(strict_types=1);

namespace App\Infra\Adapter\Jwt;

use App\Application\Security\JwtManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Symfony\Component\Security\Core\User\UserInterface;

class FirebaseJwtManager implements JwtManagerInterface
{
  private string $privateKeyPath;
  private string $publicKeyPath;
  private int $ttl;

  public function __construct(string $privateKeyPath, string $publicKeyPath, int $ttl = 3600)
  {
    $this->privateKeyPath = $privateKeyPath;
    $this->publicKeyPath = $publicKeyPath;
    $this->ttl = $ttl;
  }

  public function createToken(UserInterface $user): string
  {
    $now = time();
    $payload = [
      'iat' => $now,
      'exp' => $now + $this->ttl,
      'sub' => $user->getUserIdentifier(),
      'roles' => $user->getRoles(),
    ];

    $privateKey = file_get_contents($this->privateKeyPath);
    return JWT::encode($payload, $privateKey, 'RS256');
  }

  public function validateToken(string $token): bool
  {
    try {
      $publicKey = file_get_contents($this->publicKeyPath);
      JWT::decode($token, new Key($publicKey, 'RS256'));
      return true;
    } catch (ExpiredException $e) {
      return false;
    } catch (\Exception $e) {
      return false;
    }
  }

  public function getPayload(string $token): array
  {
    $publicKey = file_get_contents($this->publicKeyPath);
    $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
    return (array) $decoded;
  }
}
