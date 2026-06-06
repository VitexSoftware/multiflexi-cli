<?php

declare(strict_types=1);

namespace Test\MultiFlexi\Cli\Command;

use MultiFlexi\Cli\Command\UserCompany\AssignCommand as UserCompanyAssignCommand;
use MultiFlexi\Cli\Command\UserCompany\UnassignCommand as UserCompanyUnassignCommand;
use MultiFlexi\Cli\Command\UserRole\SetCommand as UserRoleSetCommand;
use Symfony\Component\Console\Application;

class UserAccessCommandsTest extends \PHPUnit\Framework\TestCase
{
    private UserCompanyAssignCommand $userCompanyAssign;
    private UserCompanyUnassignCommand $userCompanyUnassign;
    private UserRoleSetCommand $userRoleSet;

    protected function setUp(): void
    {
        $this->userCompanyAssign = new UserCompanyAssignCommand();
        $this->userCompanyUnassign = new UserCompanyUnassignCommand();
        $this->userRoleSet = new UserRoleSetCommand();
    }

    public function testCommandNames(): void
    {
        $this->assertSame('user-company:assign', $this->userCompanyAssign->getName());
        $this->assertSame('user-company:unassign', $this->userCompanyUnassign->getName());
        $this->assertSame('user-role:set', $this->userRoleSet->getName());
    }

    public function testUserCompanyAssignOptions(): void
    {
        $definition = $this->userCompanyAssign->getDefinition();

        $this->assertTrue($definition->hasOption('company_id'));
        $this->assertTrue($definition->hasOption('user_id'));
        $this->assertTrue($definition->hasOption('login'));
        $this->assertTrue($definition->hasOption('email'));
        $this->assertTrue($definition->hasOption('role'));
        $this->assertTrue($definition->hasOption('format'));
    }

    public function testUserCompanyUnassignOptions(): void
    {
        $definition = $this->userCompanyUnassign->getDefinition();

        $this->assertTrue($definition->hasOption('company_id'));
        $this->assertTrue($definition->hasOption('user_id'));
        $this->assertTrue($definition->hasOption('login'));
        $this->assertTrue($definition->hasOption('email'));
        $this->assertTrue($definition->hasOption('format'));
    }

    public function testUserRoleSetOptions(): void
    {
        $definition = $this->userRoleSet->getDefinition();

        $this->assertTrue($definition->hasOption('user_id'));
        $this->assertTrue($definition->hasOption('login'));
        $this->assertTrue($definition->hasOption('email'));
        $this->assertTrue($definition->hasOption('roles'));
        $this->assertTrue($definition->hasOption('replace'));
        $this->assertTrue($definition->hasOption('assigned_by'));
        $this->assertTrue($definition->hasOption('format'));
    }

    public function testCommandsAreRegistrable(): void
    {
        $application = new Application();
        $application->add($this->userCompanyAssign);
        $application->add($this->userCompanyUnassign);
        $application->add($this->userRoleSet);

        $this->assertSame('user-company:assign', $application->find('user-company:assign')->getName());
        $this->assertSame('user-company:unassign', $application->find('user-company:unassign')->getName());
        $this->assertSame('user-role:set', $application->find('user-role:set')->getName());
    }
}
