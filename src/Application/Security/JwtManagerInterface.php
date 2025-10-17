<?php

declare(strict_types=1);

namespace App\Application\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface JwtManagerInterface
{
  public function createToken(UserInterface $user): string;

  public function validateToken(string $token): bool;

  public function getPayload(string $token): array;
}
