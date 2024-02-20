<?php

namespace Phpkl\Tests\PklRunner;

use Phpkl\Module;
use Phpkl\Pkl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pkl::class)]
class PklRunnerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['PKL_CLI_BIN'] = __DIR__.'/../../vendor/bin/pkl';
    }

    public function testEvalSimpleFile(): void
    {
        $runner = new Pkl();
        $result = $runner->eval(__DIR__.'/../fixtures/simple.pkl');

        $this->assertInstanceOf(Module::class, $result);
        $this->assertSame('Pkl: Configure your Systems in New Ways', $result->get('name'));
        $this->assertSame(100, $result->get('attendants'));
        $this->assertTrue($result->get('isInteractive'));
        $this->assertSame(13.37, $result->get('amountLearned'));
    }

    public function testEvalMultipleConfigFiles(): void
    {
        $runner = new Pkl();
        $result = $runner->eval(__DIR__.'/../fixtures/multiple.pkl');

        $this->assertInstanceOf(Module::class, $result);

        $this->assertSame('Common wood pigeon', $result->get('woodPigeon')->get('name'));
        $this->assertSame('Seeds', $result->get('woodPigeon')->get('diet'));
        $this->assertSame('Columba palumbus', $result->get('woodPigeon')->get('taxonomy')->get('species'));

        $this->assertInstanceOf(Module::class, $result->get('stockPigeon'));
        $this->assertInstanceOf(Module::class, $result->get('dodo'));
    }
}
