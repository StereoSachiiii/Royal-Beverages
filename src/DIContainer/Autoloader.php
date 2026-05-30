<?php
declare(strict_types=1);

namespace App\DIContainer;

/**
 * PSR-4 Compliant Autoloader
 */
class Autoloader
{
    /**
     * @var array<string, array<string>>
     */
    private array $prefixes = [];

    /**
     * Register autoloader with SPL
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']); // @phpstan-ignore-line
    }

    /**
     * Add a namespace prefix to directory mapping
     *
     * @param string $prefix The namespace prefix
     * @param string $baseDir The base directory for this namespace
     * @param bool $prepend Whether to prepend to the stack
     */
    public function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        // Normalize prefix
        $prefix = trim($prefix, '\\');
        if ($prefix !== '') {
            $prefix .= '\\';
        }

        // Normalize base directory
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * Load the class file for a given class name
     *
     * @param string $class
     * @return bool
     */
    public function loadClass(string $class): bool
    {
        foreach ($this->prefixes as $prefix => $baseDirs) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $filePartial = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                foreach ($baseDirs as $baseDir) {
                    $file = $baseDir . $filePartial;
                    if (file_exists($file)) {
                        require $file;
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
