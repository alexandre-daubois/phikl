<?php

namespace Phpkl\Internal\Command;

use Phpkl\Internal\PklDownloader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class Runner
{
    public static function run(InputInterface $input, OutputInterface $output): int
    {
        $pickleDownloader = new PklDownloader();
        $io = new SymfonyStyle($input, $output);

        try {
            if (!$pickleDownloader->alreadyDownloaded($input->getOption('location')) && $input->getOption('download')) {
                $io->comment('Downloading Pkl CLI...');

                $pickleDownloader->download($io, $input->getOption('location'));
            } else {
                $io->success(sprintf('Pkl CLI is already installed in %s.', $input->getOption('location')));
            }
        } catch (\Exception $e) {
            $io->error('Pkl failed: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
