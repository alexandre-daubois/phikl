<?php

namespace Phpkl\Tests\PklRunner;

use Phpkl\PklRunner\PklConfig;
use Phpkl\PklRunner\PklRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PklRunner::class)]
class PklRunnerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['PKL_CLI_BIN'] = __DIR__.'/../../vendor/bin/pkl';
    }

    public function testEvalSimpleFile(): void
    {
        $runner = new PklRunner();
        $result = $runner->eval(__DIR__.'/../fixtures/simple.pkl');

        $this->assertInstanceOf(PklConfig::class, $result);
        $this->assertSame('Pkl: Configure your Systems in New Ways', $result->name);
        $this->assertSame(100, $result->attendants);
        $this->assertTrue($result->isInteractive);
        $this->assertSame(13.37, $result->amountLearned);
    }

    public function testEvalMultipleConfigFiles(): void
    {
        $runner = new PklRunner();
        $result = $runner->eval(__DIR__.'/../fixtures/multiple.pkl');

        $this->assertInstanceOf(PklConfig::class, $result);

        $this->assertSame('Common wood pigeon', $result->woodPigeon['name']);
        $this->assertSame('Seeds', $result->woodPigeon['diet']);
        $this->assertSame('Columba palumbus', $result->woodPigeon['taxonomy']['species']);

        $this->assertIsArray($result->stockPigeon);

        $this->assertIsArray($result->dodo);
    }
}
