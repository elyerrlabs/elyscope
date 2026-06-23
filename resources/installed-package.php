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
     * Construct
     */
    public function __construct()
    {
        $this->installedJsonPath = __DIR__ . '/vendor-build/composer/installed.json';
        $this->vendorDir = __DIR__ . '/vendor-build';
    }

    /**
     * Retrieve package json
     * @param string $installPath
     */
    private function getPackageComposerJson(string $installPath): ?array
    {
        try {
            // relative path installed.json file
            $packageJsonPath = $this->vendorDir . '/' . str_replace('../', '', $installPath) . '/composer.json';

            if (!file_exists($packageJsonPath)) {
                throw new Exception("Package composer.json not found: $packageJsonPath");
            }

            $content = file_get_contents($packageJsonPath);
            if ($content === false) {
                throw new Exception("Failed to read composer.json: $packageJsonPath");
            }

            $composerJson = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error parsing composer.json in $installPath: " . json_last_error_msg());
            }

            return $composerJson;
        } catch (Exception $e) {
            $this->displayError($e->getMessage());
            return null;
        }
    }

    public function run(): void
    {
        try {
            if (!file_exists($this->installedJsonPath)) {
                throw new Exception("installed.json not found: {$this->installedJsonPath}");
            }

            $content = file_get_contents($this->installedJsonPath);
            if ($content === false) {
                throw new Exception("Failed to read installed.json: {$this->installedJsonPath}");
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse installed.json: ' . json_last_error_msg());
            }

            if (!isset($data['packages']) || !is_array($data['packages'])) {
                throw new Exception("Invalid installed.json structure: missing 'packages' key");
            }

            foreach ($data['packages'] as &$package) {
                $this->processPackage($package);
            }

            $this->save($data);
            $this->printSummary();

        } catch (Exception $e) {
            $this->displayError($e->getMessage());
            throw $e;
        }
    }

    private function processPackage(array &$package): void
    {
        try {
            $this->stats['processed']++;

            $installPath = $package['install-path'] ?? null;

            if (!$installPath) {
                throw new Exception("Missing 'install-path' for package");
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

        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->displayError("Error processing package: " . $e->getMessage());
        }
    }

    private function save(array $data): void
    {
        try {
            if ($this->stats['updated'] === 0) {
                $this->displayInfo("No updates needed");
                return;
            }

            $content = json_encode(
                $data,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );

            if ($content === false) {
                throw new Exception('Failed to encode installed.json');
            }

            if (file_put_contents($this->installedJsonPath, $content) === false) {
                throw new Exception('Failed to write installed.json');
            }

            $this->displaySuccess("installed.json saved successfully");

        } catch (Exception $e) {
            $this->displayError($e->getMessage());
            throw $e;
        }
    }

    private function printSummary(): void
    {
        echo PHP_EOL;
        $this->displayInfo("Installed.json update completed");
        echo PHP_EOL;

        echo "Packages processed : {$this->stats['processed']}" . PHP_EOL;
        echo "Packages updated   : {$this->stats['updated']}" . PHP_EOL;

        if ($this->stats['errors'] > 0) {
            $this->displayError("Packages with errors : {$this->stats['errors']}");
        } else {
            echo "Packages with errors : 0" . PHP_EOL;
        }
    }

    /**
     * Display error message in red
     * @param string $message
     */
    private function displayError(string $message): void
    {
        echo "\033[31m[ERROR] " . $message . "\033[0m" . PHP_EOL;
    }

    /**
     * Display success message
     * @param string $message
     */
    private function displaySuccess(string $message): void
    {
        echo "\033[32m[SUCCESS] " . $message . "\033[0m" . PHP_EOL;
    }

    /**
     * Display info message
     * @param string $message
     */
    private function displayInfo(string $message): void
    {
        echo "\033[36m[INFO] " . $message . "\033[0m" . PHP_EOL;
    }
}

try {
    (new InstalledJsonUpdater())->run();

} catch (Throwable $e) {
    // Mostrar error en rojo para el usuario
    echo "\033[31m[FATAL ERROR] " . $e->getMessage() . "\033[0m" . PHP_EOL;

    // Si estamos en modo CLI, también escribir en STDERR
    if (defined('STDERR')) {
        fwrite(STDERR, '[FATAL ERROR] ' . $e->getMessage() . PHP_EOL);
    }

    exit(1);
}