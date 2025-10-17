<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

final class Register
{
  private string $email;
  private string $password;
  private string $confirmPassword;
  private string $role;

  public function __construct(string $email, string $password, string $confirmPassword, string $role)
  {
    $this->email = strtolower(trim($email));
    $this->password = $password;
    $this->confirmPassword = $confirmPassword;
    $this->role = $role;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getPassword(): string
  {
    return $this->password;
  }

  public function getConfirmPassword(): string
  {
    return $this->confirmPassword;
  }

  public function getRole(): string
  {
    return $this->role;
  }
}
