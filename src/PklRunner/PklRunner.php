<?php

namespace Phpkl\PklRunner;

use Phpkl\Module;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class PklRunner
{
    private string $executable;

    public function __construct()
    {
        $this->executable = $_ENV['PKL_CLI_BIN'] ?? $_SERVER['PKL_CLI_BIN'] ?? 'vendor/bin/pkl';

        if (!is_executable($this->executable)) {
            throw new RuntimeException('Pkl CLI is not installed. Make sure to set the PKL_CLI_BIN environment variable or run the `phpkl --download` command.');
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $toClass
     * @return T[]|T
     */
    public function eval(string $module, string $toClass = Module::class): array|object
    {
        $process = new Process([$this->executable, 'eval', '-f', 'json', $module]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $serializer = new Serializer([
            new GetSetMethodNormalizer(),
            new ObjectNormalizer(),
            new PropertyNormalizer()
        ], [new JsonEncoder()]);

        return $serializer->deserialize(trim($process->getOutput()), $toClass, 'json');
    }
}
