<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Domain\Entity\User as UserDomain;

interface JwtManagerInterface
{
  public function createToken(UserDomain $user): string;

  public function validateToken(string $token): bool;

  public function getPayload(string $token): array;
}
