<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader
 * 
 * PSR-4 compliant autoloader that supports multiple directories per namespace prefix.
 * Includes special handling for legacy global-namespace classes.
 */
class Autoloader
{
    /** @var array<string, list<string>> Maps namespace prefixes to arrays of base directories */
    private array $prefixes = [];

    /** @var list<string> Directories to search for global-namespace (non-namespaced) classes */
    private array $globalDirs = [];

    /**
     * Register the autoloader with SPL
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Add a namespace prefix to directory mapping.
     * Multiple directories can be registered for the same prefix.
     * Use '\\' or '' for global/legacy non-namespaced classes.
     *
     * @param string $prefix The namespace prefix (use '\\' for global namespace)
     * @param string $baseDir The directory containing classes for this prefix
     */
    public function addNamespace(string $prefix, string $baseDir): void
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $prefix = trim($prefix, '\\');

        // Global namespace — store separately for fallback scanning
        if ($prefix === '') {
            $this->globalDirs[] = $baseDir;
            return;
        }

        $prefix .= '\\';
        $this->prefixes[$prefix][] = $baseDir;
    }

    /**
     * Load the class file for a given class name
     *
     * @param string $class The fully-qualified class name
     * @return bool True if the file was loaded, false otherwise
     */
    public function loadClass(string $class): bool
    {
        $class = ltrim($class, '\\');

        // 1. Try namespaced prefixes first
        foreach ($this->prefixes as $prefix => $baseDirs) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                
                foreach ($baseDirs as $baseDir) {
                    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                    if (file_exists($file)) {
                        require $file;
                        return true;
                    }
                }
            }
        }

        // 2. Fallback: search global directories for non-namespaced classes
        if (!str_contains($class, '\\')) {
            foreach ($this->globalDirs as $baseDir) {
                $file = $baseDir . $class . '.php';

                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }

        return false;
    }
}
