<?php

namespace Phpkl\Internal\Command;

use Phpkl\Internal\PklDownloader;
use Phpkl\Pkl;
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
        }

        return 0;
    }

    private static function install(InputInterface $input, OutputInterface $output): int
    {
        $pickleDownloader = new PklDownloader();
        $io = new SymfonyStyle($input, $output);

        try {
            $location = rtrim($input->getOption('location'), '/');
            if (!$pickleDownloader->alreadyDownloaded($location)) {
                $io->comment('Downloading Pkl CLI...');

                $pickleDownloader->download($io, $location);
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

            return 1;
        }

        return 0;
    }
}
