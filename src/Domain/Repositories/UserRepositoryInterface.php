<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entity\User;

interface UserRepositoryInterface
{
  public function save(User $user): void;

  public function findById(string $id): ?User;

  public function findByEmail(string $email): ?User;

  public function delete(User $user): void;

  public function existsByEmail(string $email): bool;
}
