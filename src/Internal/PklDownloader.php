<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Internal;

use Phikl\Exception\PklCliAlreadyDownloadedException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class PklDownloader
{
    private const PKL_CLI_VERSION = '0.25.2';

    public function __construct()
    {
        if (!\extension_loaded('curl')) {
            throw new \RuntimeException('The curl extension is required to download the Pkl CLI. You can either install it or download the Pkl CLI manually.');
        }
    }

    public function alreadyDownloaded(string $location = 'vendor/bin'): bool
    {
        return file_exists($location.'/pkl');
    }

    public function download(SymfonyStyle $io, string $location = 'vendor/bin', bool $force = false): void
    {
        if ($this->alreadyDownloaded($location) && !$force) {
            throw new PklCliAlreadyDownloadedException('Pkl CLI is already installed.');
        }

        if ($this->is32Bit()) {
            throw new \RuntimeException('32-bit systems are not supported by Pkl CLI.');
        }

        $downloadUrl = $this->buildDownloadUrl();
        $pklCliPath = $location.\DIRECTORY_SEPARATOR.'pkl';

        $this->curlUrlToFile($downloadUrl, $location, 'pkl', $io);

        if ($this->isMacOs() || $this->isLinux()) {
            chmod($pklCliPath, 0755);
        }

        $io->comment(sprintf('Pkl CLI downloaded to %s', $pklCliPath));

        if ($location !== 'vendor/bin') {
            $io->caution('You used a custom location for the Pkl CLI. Make sure to add the location to set the PKL_CLI_BIN environment variable.');
        }

        if (str_ends_with($downloadUrl, '.jar')) {
            $io->warning('You are using the Java version of the Pkl CLI. Make sure the JDK is installed and present in your PATH.');
        }
    }

    private function curlUrlToFile(string $url, string $location, string $fileName, SymfonyStyle $io): void
    {
        $curlHandle = \curl_init($url);
        \assert($curlHandle !== false);

        $file = \fopen($location.\DIRECTORY_SEPARATOR.$fileName, 'w');

        if (!is_writable($location) && !mkdir($location, 0755, true) && !is_dir($location) || $file === false) {
            throw new \RuntimeException(sprintf('Pkl CLI could not be installed to %s, ensure the location is writable.', $location));
        }

        $progressBar = new ProgressBar($io);

        \curl_setopt($curlHandle, \CURLOPT_FILE, $file);
        \curl_setopt($curlHandle, \CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($curlHandle, \CURLOPT_NOPROGRESS, false);
        \curl_setopt($curlHandle, \CURLOPT_PROGRESSFUNCTION, function (
            mixed $resource,
            int $downloadSize,
            int $downloaded,
            int $uploadSize,
            int $uploaded
        ) use ($progressBar): void {
            if ($downloadSize > 0) {
                $progressBar->setMaxSteps($downloadSize);
                $progressBar->setProgress($downloaded);
            }
        });

        \curl_exec($curlHandle);

        if (\curl_errno($curlHandle)) {
            \fclose($file);

            throw new \RuntimeException(\curl_error($curlHandle));
        }

        \fclose($file);
        \curl_close($curlHandle);
    }

    private function buildDownloadUrl(): string
    {
        $downloadUrl = 'https://github.com/apple/pkl/releases/download/'.self::PKL_CLI_VERSION.'/pkl-';
        if ($this->isMacOs()) {
            return $downloadUrl.($this->isArmArch() ? 'macos-aarch64' : 'macos-amd64');
        } elseif ($this->isLinux()) {
            return $downloadUrl.($this->isArmArch() ? 'linux-aarch64' : 'linux-amd64');
        }

        return 'https://repo1.maven.org/maven2/org/pkl-lang/pkl-cli-java/'.self::PKL_CLI_VERSION.'/pkl-cli-java-'.self::PKL_CLI_VERSION.'.jar';
    }

    private function isArmArch(): bool
    {
        return str_contains(strtolower(php_uname('m')), 'arm');
    }

    private function isMacOs(): bool
    {
        return str_contains(strtolower(php_uname('s')), 'darwin');
    }

    private function isLinux(): bool
    {
        return str_contains(strtolower(php_uname('s')), 'linux');
    }

    private function is32Bit(): bool
    {
        return \PHP_INT_SIZE === 4;
    }
}
