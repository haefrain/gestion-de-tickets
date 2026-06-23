<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * HU-L1-E2-01: la jerarquía ROLE_CLIENT ⊂ ROLE_AGENT ⊂ ROLE_ADMIN concede permisos heredados.
 */
final class RoleHierarchyTest extends KernelTestCase
{
    private function hierarchy(): RoleHierarchyInterface
    {
        self::bootKernel();
        $hierarchy = self::getContainer()->get('security.role_hierarchy');
        \assert($hierarchy instanceof RoleHierarchyInterface);

        return $hierarchy;
    }

    public function testAdminHeredaAgenteYCliente(): void
    {
        $reachable = $this->hierarchy()->getReachableRoleNames(['ROLE_ADMIN']);

        self::assertContains('ROLE_ADMIN', $reachable);
        self::assertContains('ROLE_AGENT', $reachable);
        self::assertContains('ROLE_CLIENT', $reachable);
    }

    public function testAgenteHeredaClientePeroNoAdmin(): void
    {
        $reachable = $this->hierarchy()->getReachableRoleNames(['ROLE_AGENT']);

        self::assertContains('ROLE_AGENT', $reachable);
        self::assertContains('ROLE_CLIENT', $reachable);
        self::assertNotContains('ROLE_ADMIN', $reachable);
    }

    public function testClienteNoHeredaRolesSuperiores(): void
    {
        $reachable = $this->hierarchy()->getReachableRoleNames(['ROLE_CLIENT']);

        self::assertContains('ROLE_CLIENT', $reachable);
        self::assertNotContains('ROLE_AGENT', $reachable);
        self::assertNotContains('ROLE_ADMIN', $reachable);
    }
}
