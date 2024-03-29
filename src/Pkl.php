<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl;

use Phikl\Cache\Entry;
use Phikl\Cache\PersistentCache;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * This is the main class to interact
 * with the PKL CLI tool.
 */
class Pkl
{
    private static string $executable;
    private static ?CacheInterface $cache = null;
    private static bool $cacheEnabled = true;

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
        self::$cache ??= new PersistentCache();
        if (self::$cache instanceof PersistentCache && self::$cacheEnabled) {
            self::$cache->load();
        }

        if ((null === $entry = self::$cache->get($module)) || !self::$cacheEnabled) {
            self::initExecutable();

            $process = new Process([self::$executable, 'eval', '-f', 'json', $module]);

            try {
                $process->mustRun();
            } catch (ProcessFailedException) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $content = \trim($process->getOutput());
            $entry = new Entry($content, \md5($content), \time());

            if (self::$cacheEnabled) {
                // if we're in this condition, then it is a cache miss, thus the entry is
                // automatically refreshed/added to the cache
                if (self::$cache->set($module, $entry) && self::$cache instanceof PersistentCache) {
                    self::$cache->save();
                }
            }
        }

        /** @var PklModule $module */
        $decoded = \json_decode($entry->content, true);
        $module = new PklModule();
        foreach ($decoded as $key => $value) {
            $module->__set($key, $value);
        }

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

    /**
     * Returns the version of the PKL CLI tool.
     */
    public static function binaryVersion(?string $binPath = null): string
    {
        if ($binPath === null) {
            self::initExecutable();
        }

        $process = new Process([$binPath ?? self::$executable, '--version']);
        $process->mustRun();

        return trim($process->getOutput());
    }

    /**
     * Evaluates the given modules and returns the raw output. This method
     * is useful when you want to evaluate multiple modules at once in the
     * original format. For example:
     *
     * ```php
     * $result = Pkl::rawEval('module1', 'module2');
     * ```
     *
     * The `$result` will contain the raw output of the `pkl eval module1 module2`.
     */
    public static function rawEval(string ...$modules): string
    {
        self::initExecutable();

        $process = new Process([self::$executable, 'eval', ...$modules]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }

    /**
     * Dumps all the .pkl files in the project and returns the cache file.
     * The cache file is used to avoid calling the PKL CLI tool on every
     * `Pkl::eval()` call.
     *
     * @return int the number of warmed up files
     */
    public static function warmup(string $cacheFile): int
    {
        self::initExecutable();

        $finder = new Finder();
        $finder->files()
            ->in((string) getcwd())
            ->name('*.pkl')
            ->sortByName();

        $filenames = array_map(fn ($file) => $file->getPathname(), iterator_to_array($finder));
        $process = new Process([self::$executable, 'eval', '-f', 'json', ...$filenames]);

        $output = trim($process->mustRun()->getOutput());

        $dumpedContent = explode("\n---\n", $output);
        $dumpedContent = array_combine($filenames, $dumpedContent);

        self::$cache = new PersistentCache($cacheFile);
        foreach ($dumpedContent as $filename => $content) {
            self::$cache->set($filename, new Entry(trim($content), \md5($content), \time()));
        }

        self::$cache->save();

        return \count($dumpedContent);
    }

    /**
     * Sets the cache to use. By default, the `PersistentCache` is used.
     */
    public static function setCache(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * Whether the cache is enabled or not.
     * If the cache is disabled, the PKL CLI tool will be called on every
     * `Pkl::eval()` call.
     */
    public static function toggleCache(bool $enabled): void
    {
        self::$cacheEnabled = $enabled;
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
