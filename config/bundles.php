<?php

 $bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
];

// Register dev-only bundles only if they exist (prevents errors when --no-dev installs are used)
if (class_exists(\Symfony\Bundle\MakerBundle\MakerBundle::class)) {
    $bundles[\Symfony\Bundle\MakerBundle\MakerBundle::class] = ['dev' => true];
}

return $bundles;
