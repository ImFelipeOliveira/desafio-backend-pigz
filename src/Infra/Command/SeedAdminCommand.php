<?php

declare(strict_types=1);

namespace App\Infra\Command;

use App\Domain\Entity\User;
use App\Domain\Repositories\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
  name: 'app:seed:admin',
  description: 'Create an admin user for testing',
)]
class SeedAdminCommand extends Command
{
  public function __construct(
    private readonly UserRepositoryInterface $userRepository
  ) {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin email', 'admin@pigz.com')
      ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', 'admin123');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $email = $input->getOption('email');
    $password = $input->getOption('password');

    if ($this->userRepository->existsByEmail($email)) {
      $io->warning(sprintf('User with email "%s" already exists', $email));
      return Command::FAILURE;
    }

    try {
      $admin = User::register(
        (string) Uuid::v7(),
        $email,
        $password,
        ['ROLE_USER', 'ROLE_ADMIN']
      );

      $this->userRepository->save($admin);

      $io->success(sprintf('Admin user created successfully! Email: %s, Password: %s', $email, $password));
      $io->note('You can now login with these credentials');

      return Command::SUCCESS;
    } catch (\Exception $e) {
      $io->error(sprintf('Failed to create admin user: %s', $e->getMessage()));
      return Command::FAILURE;
    }
  }
}
