<?php

namespace Phpkl;

use Phpkl\Exception\PklCliAlreadyDownloadedException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PklDownloader
{
    private const PKL_CLI_VERSION = '0.25.2';
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    public function alreadyDownloaded(string $location = 'vendor/bin'): bool
    {
        return file_exists($location.'/pkl');
    }

    public function download(string $location = 'vendor/bin', SymfonyStyle $io = null): void
    {
        if ($this->alreadyDownloaded($location)) {
            throw new PklCliAlreadyDownloadedException('Pkl CLI is already installed.');
        }

        if ($this->is32Bit()) {
            throw new \RuntimeException('32-bit systems are not supported by Pkl CLI.');
        }

        if ($io !== null) {
            $progressBar = new ProgressBar($io);
            // set the progress bar format to display how many megabytes are downloaded
            $progressBar->setFormat('verbose');
        }

        $downloadUrl = $this->buildDownloadUrl();
        $response = $this->httpClient->request('GET', $downloadUrl, [
            'on_progress' => $io === null ? null : function ($dlNow, $dlSize) use ($progressBar) {
                // set the progress bar format to display how many megabytes are downloaded
                $progressBar->setProgress($dlNow / 1e+6);
                $progressBar->setMaxSteps($dlSize / 1e+6);
            },
        ]);

        if (!is_writable($location) && !mkdir($location, 0755, true) && !is_dir($location)) {
            throw new \RuntimeException(sprintf('Pkl CLI could not be installed to %s, ensure the directory is writable.', $location));
        }

        $pklCliPath = $location.'/pkl';
        file_put_contents($pklCliPath, $response->getContent());

        if ($this->isMacOs() || $this->isLinux()) {
            chmod($pklCliPath, 0755);
        }

        $io->success(sprintf('Pkl CLI downloaded successfully to %s', $pklCliPath));

        if ($location !== 'vendor/bin') {
            $io->warning('You used a custom location for the Pkl CLI. Make sure to add the location to set the PKL_CLI_BIN environment variable.');
        }

        if (str_ends_with($downloadUrl, '.jar')) {
            $io->warning('You are using the Java version of the Pkl CLI. Make sure the JDK is installed and present in your PATH.');
        }
    }

    private function buildDownloadUrl() : string
    {
        $downloadUrl = 'https://github.com/apple/pkl/releases/download/'.self::PKL_CLI_VERSION.'/pkl-';
        if ($this->isMacOs()) {
            return $downloadUrl.($this->isArmArch() ? 'macos-aarch64' : 'macos-amd64');
        } elseif ($this->isLinux()) {
            return $downloadUrl.($this->isArmArch() ? 'linux-aarch64' : 'linux-amd64');
        }

        return 'https://repo1.maven.org/maven2/org/pkl-lang/pkl-cli-java/0.25.2/pkl-cli-java-'.self::PKL_CLI_VERSION.'.jar';
    }

    private function isArmArch(): string
    {
        return str_contains(strtolower(php_uname('m')), 'arm');
    }

    private function isMacOs(): string
    {
        return str_contains(strtolower(php_uname('s')), 'darwin');
    }

    private function isLinux(): string
    {
        return str_contains(strtolower(php_uname('s')), 'linux');
    }

    private function is32Bit(): string
    {
        return \PHP_INT_SIZE === 4;
    }
}
