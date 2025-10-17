<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class User
{
  private Uuid $id;
  private string $email;
  private string $passwordHash;
  /** @var list<string> */
  private array $roles;
  private DateTimeImmutable $createdAt;
  private DateTimeImmutable $updatedAt;

  private function __construct(
    Uuid $id,
    string $email,
    string $passwordHash,
    array $roles,
    DateTimeImmutable $createdAt,
    DateTimeImmutable $updatedAt,
  ) {
    $this->assertValidEmail($email);
    $this->assertValidRoles($roles);

    $this->id = $id;
    $this->email = $email;
    $this->passwordHash = $passwordHash;
    $this->roles = array_values(array_unique($roles));
    $this->createdAt = $createdAt;
    $this->updatedAt = $updatedAt;
  }

  public static function register(string $email, string $plainPassword, array $roles = ['ROLE_USER']): self
  {
    $now = new DateTimeImmutable();

    return new self(
      Uuid::v4(),
      $email,
      password_hash($plainPassword, PASSWORD_ARGON2ID),
      $roles,
      $now,
      $now,
    );
  }

  public static function restore(
    Uuid $id,
    string $email,
    string $passwordHash,
    array $roles,
    DateTimeImmutable $createdAt,
    DateTimeImmutable $updatedAt,
  ): self {
    return new self($id, $email, $passwordHash, $roles, $createdAt, $updatedAt);
  }

  public function changeEmail(string $newEmail): void
  {
    if ($newEmail === $this->email) {
      return;
    }

    $this->assertValidEmail($newEmail);
    $this->email = $newEmail;
    $this->touch();
  }

  public function setPassword(string $plainPassword): void
  {
    $this->passwordHash = password_hash($plainPassword, PASSWORD_ARGON2ID);
    $this->touch();
  }

  public function promote(string $role): void
  {
    if (!in_array($role, $this->roles, true)) {
      $this->roles[] = $role;
      $this->touch();
    }
  }

  public function demote(string $role): void
  {
    $this->roles = array_values(array_filter(
      $this->roles,
      static fn(string $current) => $current !== $role
    ));

    $this->assertValidRoles($this->roles);
    $this->touch();
  }

  public function verifyPassword(string $plainPassword): bool
  {
    return password_verify($plainPassword, $this->passwordHash);
  }

  public function needsPasswordRehash(): bool
  {
    return password_needs_rehash($this->passwordHash, PASSWORD_ARGON2ID);
  }

  public function getId(): Uuid
  {
    return $this->id;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getPasswordHash(): string
  {
    return $this->passwordHash;
  }

  /** @return list<string> */
  public function getRoles(): array
  {
    return $this->roles;
  }

  public function getCreatedAt(): DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function getUpdatedAt(): DateTimeImmutable
  {
    return $this->updatedAt;
  }

  private function touch(): void
  {
    $this->updatedAt = new DateTimeImmutable();
  }

  private function assertValidEmail(string $email): void
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException('Invalid email address.');
    }
  }

  /** @param list<string> $roles */
  private function assertValidRoles(array $roles): void
  {
    if ($roles === []) {
      throw new \InvalidArgumentException('User must have at least one role.');
    }

    foreach ($roles as $role) {
      if (!is_string($role) || $role === '') {
        throw new \InvalidArgumentException('Role list must contain non-empty strings.');
      }
    }
  }
}
