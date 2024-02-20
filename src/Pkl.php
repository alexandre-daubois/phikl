<?php

namespace Phpkl;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class Pkl
{
    private static string $executable;

    /**
     * @template T of object
     *
     * @param class-string<T> $toClass
     *
     * All properties will be cast to `$toClass` class, if different
     * from PklModule. For example, the following module will
     * be cast to two `$toClass` instances:
     *
     * ```pkl
     * user1 {
     *     id: 1
     * }
     *
     * user2 {
     *    id: 2
     * }
     * ```
     *
     * @return array<T>|PklModule
     */
    public static function eval(string $module, string $toClass = PklModule::class): array|PklModule
    {
        self::initExecutable();

        $process = new Process([self::$executable, 'eval', '-f', 'json', $module]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $serializer = new Serializer([
            new GetSetMethodNormalizer(),
            new ObjectNormalizer(),
            new PropertyNormalizer(),
        ], [new JsonEncoder()]);

        /** @var PklModule $module */
        $module = $serializer->deserialize(trim($process->getOutput()), PklModule::class, 'json');
        if ($toClass === PklModule::class) {
            return $module;
        }

        $instances = [];
        foreach ($module->keys() as $key) {
            if (!$module->get($key) instanceof PklModule) {
                throw new \RuntimeException(sprintf('The module "%s" is not a PklModule instance.', $key));
            }

            $instances[$key] = $module->get($key)->cast($toClass);
        }

        return $instances;
    }

    public static function binaryVersion(?string $binPath = null): string
    {
        if ($binPath === null) {
            self::initExecutable();
        }

        $process = new Process([$binPath ?? self::$executable, '--version']);
        $process->mustRun();

        return trim($process->getOutput());
    }

    private static function initExecutable(): void
    {
        self::$executable ??= (function () {
            $exec = $_ENV['PKL_CLI_BIN'] ?? $_SERVER['PKL_CLI_BIN'] ?? 'vendor/bin/pkl';

            if (!is_executable($exec)) {
                throw new \RuntimeException('Pkl CLI is not installed. Make sure to set the PKL_CLI_BIN environment variable or run the `phikl install` command.');
            }

            return $exec;
        })();
    }
}
