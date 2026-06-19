<?php

class InstalledJsonUpdater
{
    /**
     * Installed.json path
     * @var string
     */
    private string $installedJsonPath;

    /**
     * Vendor directory
     * @var string
     */
    private string $vendorDir;

    /**
     * Status
     * @var array
     */
    private array $stats = [
        'processed' => 0,
        'updated' => 0,
        'errors' => 0,
    ];

    /**
     * Conatruct
     */
    public function __construct()
    {
        $this->installedJsonPath = __DIR__ . '/vendor/composer/installed.json';
        $this->vendorDir = __DIR__ . '/vendor';
    }

    /**
     * Retrieve package json
     * @param string $installPath
     */
    private function getPackageComposerJson(string $installPath): ?array
    {
        // relative path installed.json file
        $packageJsonPath = $this->vendorDir . '/' . str_replace('../', '', $installPath) . '/composer.json';

        if (!file_exists($packageJsonPath)) {
            return null;
        }

        $content = file_get_contents($packageJsonPath);
        if ($content === false) {
            return null;
        }

        $composerJson = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error parsing composer.json in $installPath: " . json_last_error_msg() . "\n";
            return null;
        }

        return $composerJson;
    }

    public function run(): void
    {
        if (!file_exists($this->installedJsonPath)) {
            throw new RuntimeException(
                "installed.json not found: {$this->installedJsonPath}"
            );
        }

        $content = file_get_contents($this->installedJsonPath);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to parse installed.json: ' . json_last_error_msg()
            );
        }

        if (!isset($data['packages']) || !is_array($data['packages'])) {
            throw new RuntimeException(
                "Invalid installed.json structure: missing 'packages' key"
            );
        }

        foreach ($data['packages'] as &$package) {
            $this->processPackage($package);
        }

        $this->save($data);

        $this->printSummary();
    }


    private function processPackage(array &$package): void
    {
        $this->stats['processed']++;

        $installPath = $package['install-path'] ?? null;

        if (!$installPath) {
            return;
        }

        $composerJson = $this->getPackageComposerJson($installPath);

        if ($composerJson === null) {
            $this->stats['errors']++;
            return;
        }

        $updated = false;

        foreach (['autoload', 'extra', 'autoload-dev'] as $key) {

            if (!isset($composerJson[$key])) {
                continue;
            }

            if (($package[$key] ?? null) !== $composerJson[$key]) {
                $package[$key] = $composerJson[$key];
                $updated = true;
            }
        }

        if ($updated) {
            $this->stats['updated']++;
        }
    }


    private function save(array $data): void
    {
        if ($this->stats['updated'] === 0) {
            return;
        }

        $content = json_encode(
            $data,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        if ($content === false) {
            throw new RuntimeException(
                'Failed to encode installed.json'
            );
        }

        if (file_put_contents($this->installedJsonPath, $content) === false) {
            throw new RuntimeException(
                'Failed to write installed.json'
            );
        }
    }

    private function printSummary(): void
    {
        echo PHP_EOL;
        echo "Installed.json updated successfully" . PHP_EOL;
        echo PHP_EOL;

        echo "Packages processed : {$this->stats['processed']}" . PHP_EOL;
        echo "Packages updated   : {$this->stats['updated']}" . PHP_EOL;
        echo "Packages skipped   : {$this->stats['errors']}" . PHP_EOL;
    }
}

try {

    (new InstalledJsonUpdater())->run();

} catch (Throwable $e) {

    fwrite(
        STDERR,
        '[ERROR] ' . $e->getMessage() . PHP_EOL
    );

    exit(1);
}