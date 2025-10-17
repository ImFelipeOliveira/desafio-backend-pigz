<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

final class Credentials
{
  private string $email;
  private string $password;

  public function __construct(string $email, string $password)
  {
    $this->email = strtolower(trim($email));
    $this->password = $password;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getPassword(): string
  {
    return $this->password;
  }
}
