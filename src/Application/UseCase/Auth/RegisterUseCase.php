<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\DTO\Auth\Register;
use App\Application\UseCase\UseCaseInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Domain\Entity\User;

class RegisterUseCase implements UseCaseInterface
{

  public function __construct(
    private UserRepositoryInterface $userRepository
  ) {}
  /**
   * @param Register $input
   */
  public function execute(mixed $input): array
  {
    $this->verifyInputInstaceOf($input);
    $this->verifyEmailIsAvailable($input->getEmail());
    $this->comparePasswords($input->getPassword(), $input->getConfirmPassword());
    $user = User::register(
      $input->getEmail(),
      $input->getPassword(),
      [$input->getRole()]
    );

    $this->userRepository->save($user);

    return [];
  }

  private function verifyInputInstaceOf(mixed $input): void
  {
    if (!$input instanceof Register) {
      throw new \InvalidArgumentException(
        'Input must be an instance of Register',
        code: JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function verifyEmailIsAvailable(string $email): void
  {
    $user = $this->userRepository->findByEmail($email);
    if ($user !== null) {
      throw new \InvalidArgumentException(
        'Email is already in use.',
        code: JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function comparePasswords(string $password, string $confirmPassword): void
  {
    if ($password !== $confirmPassword) {
      throw new \InvalidArgumentException(
        'Passwords do not match.',
        code: JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }
}
