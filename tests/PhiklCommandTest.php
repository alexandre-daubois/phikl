<?php

namespace Phpkl\Tests;

use Phpkl\Internal\Command\Runner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

#[CoversClass(Runner::class)]
class PhiklCommandTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['PKL_CLI_BIN'] = __DIR__.'/../vendor/bin/pkl';
    }

    public function testVersion(): void
    {
        $process = new Process(['php', __DIR__.'/../phikl', 'version']);
        $process->mustRun();

        $this->assertMatchesRegularExpression('/(.*)Running Pkl \d+.\d+.\d+ \(.+\)/', $process->getOutput());
    }

    public function testInstallAlreadyPresent(): void
    {
        $process = new Process(['php', __DIR__.'/../phikl', 'install']);
        $process->mustRun();

        $this->assertMatchesRegularExpression('/(.*)Pkl CLI is already installed in (.+)/', $process->getOutput());
    }

    public function testCanEval(): void
    {
        $process = new Process(['php', __DIR__.'/../phikl', 'eval', 'tests/Fixtures/user.pkl']);
        $process->mustRun();

        $this->assertSame(<<<PKL
user {
  id = 1
  name = "John Doe"
  address {
    street = "123 Main St"
    city = "Springfield"
    state = "IL"
    zip = "62701"
  }
}
PKL, trim($process->getOutput()));
    }

    public function testCanDump(): void
    {
        $process = new Process(['php', __DIR__.'/../phikl', 'dump', 'tests/Fixtures/simple.pkl']);
        $process->mustRun();

        $this->assertFileExists('.phikl.cache');
        unlink('.phikl.cache');
    }

    public function testCanValidateCache(): void
    {
        $process = new Process(['php', __DIR__.'/../phikl', 'dump', 'tests/Fixtures/simple.pkl']);
        $process->mustRun();

        $process = new Process(['php', __DIR__.'/../phikl', 'validate-cache']);
        $process->mustRun();

        $this->assertMatchesRegularExpression('/(.*)\[OK] Cache file "(.+)" is valid/', $process->getOutput());

        unlink('.phikl.cache');
    }
}
