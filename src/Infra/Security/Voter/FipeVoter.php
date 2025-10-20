<?php

declare(strict_types=1);

namespace App\Infra\Security\Voter;

use App\Domain\Entity\FipeEntry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter para controle de acesso em operações de FipeEntry
 * 
 * Regras (conforme requisito):
 * - ROLE_ADMIN pode fazer qualquer operação (CREATE, EDIT, DELETE)
 * - ROLE_USER pode apenas visualizar (VIEW)
 */
class FipeVoter extends Voter
{
  public const CREATE = 'FIPE_CREATE';
  public const EDIT = 'FIPE_EDIT';
  public const DELETE = 'FIPE_DELETE';
  public const VIEW = 'FIPE_VIEW';
  public const SYNC = 'FIPE_SYNC';

  protected function supports(string $attribute, mixed $subject): bool
  {
    if (!in_array($attribute, [self::CREATE, self::EDIT, self::DELETE, self::VIEW, self::SYNC])) {
      return false;
    }

    if (in_array($attribute, [self::CREATE, self::SYNC])) {
      return true;
    }

    return $subject instanceof FipeEntry;
  }

  protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
  {
    $user = $token->getUser();

    if (!$user instanceof UserInterface) {
      return false;
    }

    if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
      return true;
    }

    return match ($attribute) {
      self::CREATE, self::EDIT, self::DELETE, self::SYNC => false,
      self::VIEW => true,
      default => false,
    };
  }
}
