<?php

namespace Phpkl;

use Phpkl\PklRunner\PklConfig;
use Phpkl\PklRunner\PklRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Runner
{
    public static function run(InputInterface $input, OutputInterface $output): int
    {
        $pickleDownloader = new PklDownloader();
        $io = new SymfonyStyle($input, $output);

        try {
            if (!$pickleDownloader->alreadyDownloaded($input->getOption('location')) && $input->getOption('download')) {
                $io->comment('Downloading Pkl CLI...');

                $pickleDownloader->download($input->getOption('location'), $io);
            }

            $runner = new PklRunner();
            dump($runner->eval(__DIR__.'/../tests/fixtures/simple.pkl'));
        } catch (\Exception $e) {
            $io->error("Pkl failed: ".$e->getMessage());

            return 1;
        }

        return 0;
    }
}
