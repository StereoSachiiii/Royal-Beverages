<?php
declare(strict_types=1);

namespace App\DIContainer;

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use Closure;

/**
 * Proper Dependency Injection Container with Auto-wiring
 */
class Container
{
    /**
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * @var array<string, bool> Track classes currently being resolved to detect circular dependencies
     */
    private array $resolving = [];

    /**
     * @var array<string, ReflectionClass<object>> Cache of ReflectionClass instances
     */
    private static array $reflectionCache = [];

    /**
     * @var array<string, array|null> Cache of constructor dependencies
     */
    private static array $dependenciesCache = [];

    /**
     * Bind an abstract type to a concrete implementation
     */
    public function bind(string $abstract, mixed $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Bind an abstract type as a singleton
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a singleton
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Check if the container can resolve the given identifier
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    /**
     * Resolve the given identifier from the container
     *
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function get(string $id): mixed
    {
        // 1. Return instance if it's already a singleton
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Circular Dependency Detection
        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency detected while resolving '{$id}'. Stack: " . implode(' -> ', array_keys($this->resolving)));
        }

        $this->resolving[$id] = true;

        try {
            // 3. Determine what to build
            if (isset($this->bindings[$id])) {
                $concrete = $this->bindings[$id]['concrete'];
            } else {
                $concrete = $id;
            }

            // 4. Handle Closures (Factories)
            if ($concrete instanceof Closure) {
                $object = $concrete($this);
            } else {
                // 5. Handle class strings via Reflection (Auto-wiring)
                $object = $this->resolve($concrete);
            }

            // 6. Store if it's a singleton
            if (isset($this->bindings[$id]) && $this->bindings[$id]['singleton']) {
                $this->instances[$id] = $object;
            }

            return $object;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * The internal resolution engine (Auto-wiring)
     *
     * @param string $class
     * @return object
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new NotFoundException("Class '{$class}' does not exist and cannot be resolved.");
        }

        // Use cached ReflectionClass if available
        if (isset(self::$reflectionCache[$class])) {
            $reflection = self::$reflectionCache[$class];
        } else {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                throw new ContainerException("Class '{$class}' is not instantiable (it might be an interface or abstract class).");
            }
            self::$reflectionCache[$class] = $reflection;
        }

        // Use cached dependencies if available
        if (array_key_exists($class, self::$dependenciesCache)) {
            $parameters = self::$dependenciesCache[$class];
        } else {
            $constructor = $reflection->getConstructor();
            $parameters = $constructor ? $constructor->getParameters() : [];
            self::$dependenciesCache[$class] = $parameters;
        }

        // If constructor has no parameters, just new it up
        if (empty($parameters)) {
            return $reflection->newInstance();
        }

        // Recursively resolve dependencies
        $dependencies = array_map(function (ReflectionParameter $param) use ($class) {
            $name = $param->getName();
            $type = $param->getType();

            if (!$type) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw new ContainerException("Cannot resolve parameter '{$name}' in '{$class}' because it has no type hint and no default value.");
            }

            if (!($type instanceof ReflectionNamedType) || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw new ContainerException("Cannot resolve built-in parameter '{$name}' in '{$class}' without a default value.");
            }

            $dependentClass = $type->getName();
            
            try {
                return $this->get($dependentClass);
            } catch (NotFoundException $e) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw $e;
            }
        }, $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }
}
