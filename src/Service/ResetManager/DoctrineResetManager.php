<?php

declare(strict_types=1);

namespace BehatOrmContext\Service\ResetManager;

use Symfony\Component\HttpKernel\KernelInterface;

class DoctrineResetManager implements ResetManagerInterface
{
    public function needsReset(string $httpMethod): bool
    {
        return in_array(strtoupper($httpMethod), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public function reset(KernelInterface $kernel): void
    {
        $container = $kernel->getContainer();

        if ($container->hasParameter('doctrine.entity_managers')) {
            /** @var array $entityManagers */
            $entityManagers = $container->getParameter('doctrine.entity_managers');

            foreach ($entityManagers as $entityManagerId) {
                if ($container->initialized($entityManagerId)) {
                    $em = $container->get($entityManagerId);
                    $em->clear();

                    $connection = $em->getConnection();

                    if ($connection->isConnected()) {
                        $connection->close();
                    }
                }
            }
        }
    }
}
