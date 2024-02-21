<?php

namespace Phpkl\Internal\Command;

use Phpkl\Cache\Cache;
use Phpkl\Exception\CorruptedCacheException;
use Phpkl\Exception\EmptyCacheException;
use Phpkl\Internal\PklDownloader;
use Phpkl\Pkl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @internal
 */
final class Runner
{
    public static function run(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('subcommand') === 'install') {
            return self::install($input, $output);
        } elseif ($input->getArgument('subcommand') === 'update') {
            return self::install($input, $output, true);
        } elseif ($input->getArgument('subcommand') === 'version') {
            return self::version($input, $output);
        } elseif ($input->getArgument('subcommand') === 'eval') {
            return self::eval($input, $output);
        } elseif ($input->getArgument('subcommand') === 'dump') {
            return self::dump($input, $output);
        } elseif ($input->getArgument('subcommand') === 'validate-cache') {
            return self::validateCache($input, $output);
        }

        return Command::INVALID;
    }

    private static function install(InputInterface $input, OutputInterface $output, bool $force = false): int
    {
        $pickleDownloader = new PklDownloader();
        $io = new SymfonyStyle($input, $output);

        try {
            $location = rtrim($input->getOption('location'), '/');
            if (!$pickleDownloader->alreadyDownloaded($location) || $force) {
                $io->comment('Downloading Pkl CLI...');

                $pickleDownloader->download($io, $location, $force);
            } else {
                $io->success(sprintf('Pkl CLI is already installed in %s.', $location));
            }

            try {
                $io->success('Running '.Pkl::binaryVersion($location.'/pkl'));
            } catch (ProcessFailedException) {
                throw new \RuntimeException('Pkl CLI could not be installed, make sure the location is in your PATH.');
            }
        } catch (\Exception $e) {
            $io->error('Pkl failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private static function version(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->success('Running '.Pkl::binaryVersion());
        } catch (ProcessFailedException) {
            throw new \RuntimeException('Pkl CLI could not be installed, make sure the location is in your PATH.');
        }

        return Command::SUCCESS;
    }

    private static function eval(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->writeln(Pkl::rawEval(...$input->getArgument('args')));
        } catch (\Exception $e) {
            $io->error('Pkl failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private static function dump(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cacheFile = $input->getOption('cache-file') ?? self::guessCacheFile();

        try {
            $cacheFile = Pkl::dump($cacheFile);

            $io->success(sprintf('Cache file dumped to "%s"', $cacheFile));

            if ($input->getOption('cache-file') !== '.phikl.cache') {
                $io->caution('Make sure to declare the PHIKL_CACHE_FILE environment variable to use the cache file.');
            }
        } catch (\Exception $e) {
            $io->error('Pkl failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private static function validateCache(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cacheFile = $input->getOption('cache-file') ?? self::guessCacheFile();

        if (!file_exists($cacheFile)) {
            $io->warning(sprintf('Cache file "%s" does not exist, it can be generated with the `phikl dump` command.', $cacheFile));

            return Command::FAILURE;
        }

        try {
            $cache = new Cache();
            $cache->setCacheFile($cacheFile);
            $cache->validate();

            $io->success(sprintf('Cache file "%s" is valid.', $cacheFile));
        } catch (CorruptedCacheException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (EmptyCacheException $e) {
            $io->warning($e->getMessage());
        }

        return Command::SUCCESS;
    }

    private static function guessCacheFile(): string
    {
        return $_ENV['PHIKL_CACHE_FILE'] ?? $_SERVER['PHIKL_CACHE_FILE'] ?? '.phikl.cache';
    }
}
