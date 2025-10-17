<?php

declare(strict_types=1);

namespace App\Infra\Adapter\Jwt;

use App\Application\Security\JwtManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Domain\Entity\User as UserDomain;

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

  public function createToken(UserDomain $user): string
  {
    $now = time();
    $payload = [
      'iat' => $now,
      'exp' => $now + $this->ttl,
      'sub' => $user->getEmail(),
      'roles' => $user->getRoles(),
    ];

    $privateKey = file_get_contents($this->privateKeyPath);
    return JWT::encode($payload, $privateKey, 'RS256');
  }

  public function validateToken(string $token): bool
  {
    try {
      $this->getPayload($token);
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
