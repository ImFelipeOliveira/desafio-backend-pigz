<?php

declare(strict_types=1);

namespace App\Infra\Security\Voter;

use App\Domain\Entity\Vehicle;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter para controle de acesso granular em operações de Vehicle
 * 
 * Regras:
 * - ROLE_ADMIN pode fazer qualquer operação (CREATE, EDIT, DELETE, VIEW)
 * - ROLE_USER pode apenas visualizar (VIEW)
 * - Owner do veículo pode editar/deletar seu próprio veículo
 */
class VehicleVoter extends Voter
{
  public const CREATE = 'VEHICLE_CREATE';
  public const EDIT = 'VEHICLE_EDIT';
  public const DELETE = 'VEHICLE_DELETE';
  public const VIEW = 'VEHICLE_VIEW';

  protected function supports(string $attribute, mixed $subject): bool
  {
    if (!in_array($attribute, [self::CREATE, self::EDIT, self::DELETE, self::VIEW])) {
      return false;
    }
    if ($attribute === self::CREATE) {
      return true;
    }
    return $subject instanceof Vehicle;
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
      self::CREATE => $this->canCreate($user),
      self::VIEW => $this->canView($user, $subject),
      self::EDIT => $this->canEdit($user, $subject),
      self::DELETE => $this->canDelete($user, $subject),
      default => false,
    };
  }

  private function canCreate(UserInterface $user): bool
  {
    return in_array('ROLE_ADMIN', $user->getRoles(), true);
  }

  private function canView(UserInterface $user, Vehicle $vehicle): bool
  {
    return true;
  }

  private function canEdit(UserInterface $user, Vehicle $vehicle): bool
  {
    if ($vehicle->getOwnerId() === $user->getUserIdentifier()) {
      return true;
    }
    return false;
  }

  private function canDelete(UserInterface $user, Vehicle $vehicle): bool
  {
    if ($vehicle->getOwnerId() === $user->getUserIdentifier()) {
      return true;
    }
    return false;
  }
}
