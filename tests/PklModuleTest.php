<?php

namespace Phpkl\Tests;

use Phpkl\Pkl;
use Phpkl\PklModule;
use Phpkl\Tests\Fixtures\Address;
use Phpkl\Tests\Fixtures\ClassWithAttributes;
use Phpkl\Tests\Fixtures\User;
use Phpkl\Tests\Fixtures\UserWithArrayAddress;
use Phpkl\Tests\Fixtures\UserWithAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PklModule::class)]
class PklModuleTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['PKL_CLI_BIN'] = __DIR__.'/../vendor/bin/pkl';
    }

    public function testCastWithAttributedProperties(): void
    {
        /** @var PklModule $module */
        $module = Pkl::eval(__DIR__.'/Fixtures/name_surname.pkl');
        $class = $module->cast(ClassWithAttributes::class);

        $this->assertSame('Alex', $class->name);
        $this->assertSame('Daubois', $class->surname);
    }

    public function testCastNested(): void
    {
        /** @var PklModule $module */
        $module = Pkl::eval(__DIR__.'/Fixtures/user.pkl');
        $class = $module->get('user');

        $this->assertInstanceOf(PklModule::class, $class);
        $class = $class->cast(User::class);

        $this->assertSame(1, $class->id);
        $this->assertSame('John Doe', $class->name);

        $this->assertInstanceOf(Address::class, $class->address);
        $this->assertSame('62701', $class->address->zip);
        $this->assertSame('123 Main St', $class->address->street);
        $this->assertSame('IL', $class->address->state);
        $this->assertSame('Springfield', $class->address->city);
    }

    public function testCastNestedArray(): void
    {
        /** @var PklModule $module */
        $module = Pkl::eval(__DIR__.'/Fixtures/user.pkl');
        $class = $module->get('user');

        $this->assertInstanceOf(PklModule::class, $class);
        $class = $class->cast(UserWithArrayAddress::class);

        $this->assertSame(1, $class->id);
        $this->assertSame('John Doe', $class->name);

        $this->assertIsArray($class->address);
        $this->assertSame('62701', $class->address['zip']);
        $this->assertSame('123 Main St', $class->address['street']);
        $this->assertSame('IL', $class->address['state']);
        $this->assertSame('Springfield', $class->address['city']);
    }

    public function testCastNestedWithAttributes(): void
    {
        /** @var PklModule $module */
        $module = Pkl::eval(__DIR__.'/Fixtures/user.pkl');

        $class = $module->get('user');

        $this->assertInstanceOf(PklModule::class, $class);
        $class = $class->cast(UserWithAttributes::class);

        $this->assertSame(1, $class->identifier);
        $this->assertSame('John Doe', $class->nameOfUser);

        $this->assertInstanceOf(Address::class, $class->addressOfUser);
        $this->assertSame('62701', $class->addressOfUser->zip);
        $this->assertSame('123 Main St', $class->addressOfUser->street);
        $this->assertSame('IL', $class->addressOfUser->state);
        $this->assertSame('Springfield', $class->addressOfUser->city);
    }

    public function testAmends(): void
    {
        /** @var PklModule $module */
        $module = Pkl::eval(__DIR__.'/Fixtures/amends.pkl');

        $this->assertInstanceOf(PklModule::class, $module->get('bird'));
        $this->assertInstanceOf(PklModule::class, $module->get('parrot'));

        $this->assertInstanceOf(PklModule::class, $module->get('bird')->get('taxonomy'));
        $this->assertInstanceOf(PklModule::class, $module->get('parrot')->get('taxonomy'));

        $this->assertSame('Animalia', $module->get('bird')->get('taxonomy')->get('kingdom'));
        $this->assertSame('Animalia', $module->get('parrot')->get('taxonomy')->get('kingdom'));

        $this->assertSame('Dinosauria', $module->get('bird')->get('taxonomy')->get('clade'));
        $this->assertSame('Dinosauria', $module->get('parrot')->get('taxonomy')->get('clade'));

        // amended
        $this->assertSame('Columbiformes', $module->get('bird')->get('taxonomy')->get('order'));
        $this->assertSame('Psittaciformes', $module->get('parrot')->get('taxonomy')->get('order'));

        // amended
        $this->assertSame('Seeds', $module->get('bird')->get('diet'));
        $this->assertSame('Berries', $module->get('parrot')->get('diet'));
    }
}
