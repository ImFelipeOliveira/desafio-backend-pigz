<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\DTO\Auth\Credentials;
use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Application\Security\JwtManagerInterface;

class LoginUseCase implements UseCaseInterface
{
  public function __construct(
    private UserRepositoryInterface $userRepository,
    private JwtManagerInterface $jwtManager
  ) {}

  /**
   * @param Credentials|array $input
   */
  public function execute(mixed $input): array
  {
    if (!$input instanceof Credentials) {
      throw new \InvalidArgumentException('Input must be an instance of Credentials');
    }
    $user = $this->getUser($input->getEmail());
    $this->verifyPassword($user, $input->getPassword());
    $token = $this->jwtManager->createToken($user);

    return [
      'token' => $token,
    ];
  }

  private function getUser(string $email): User
  {
    $user = $this->userRepository->findByEmail($email);
    if (!$user) {
      throw new \InvalidArgumentException('Invalid credentials.');
    }
    return $user;
  }

  private function verifyPassword(
    User $user,
    string $plainPassword
  ): void {
    if (!$user->verifyPassword($plainPassword)) {
      throw new \InvalidArgumentException('Invalid credentials.');
    }

    if ($user->needsPasswordRehash()) {
      $user->setPassword($plainPassword);
      $this->userRepository->save($user);
    }
  }
}
