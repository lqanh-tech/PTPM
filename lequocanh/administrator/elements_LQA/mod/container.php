<?php

class Container {
    private static $instance = null;
    private $bindings = [];
    private $instances = [];
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function bind($abstract, $concrete = null, $singleton = false) {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    public function singleton($abstract, $concrete = null) {
        $this->bind($abstract, $concrete, true);
    }
    
    public function instance($abstract, $instance) {
        $this->instances[$abstract] = $instance;
    }
    
    public function make($abstract, $parameters = []) {

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        if (!isset($this->bindings[$abstract])) {

            if (class_exists($abstract)) {
                return $this->build($abstract, $parameters);
            }
            throw new Exception("No binding found for {$abstract}");
        }
        
        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];
        
        if ($concrete instanceof Closure) {
            $instance = $concrete($this, $parameters);
        } else {
            $instance = $this->build($concrete, $parameters);
        }
        
        if ($binding['singleton']) {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    private function build($concrete, $parameters = []) {
        if (!class_exists($concrete)) {
            throw new Exception("Class {$concrete} does not exist");
        }
        
        $reflector = new ReflectionClass($concrete);
        
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        if ($constructor === null) {
            return new $concrete;
        }
        
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);
        
        return $reflector->newInstanceArgs($dependencies);
    }
    
    private function resolveDependencies($parameters, $primitives = []) {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            
            if ($dependency === null) {

                if (isset($primitives[$parameter->name])) {
                    $dependencies[] = $primitives[$parameter->name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve primitive parameter {$parameter->name}");
                }
            } else {

                $dependencies[] = $this->make($dependency->name);
            }
        }
        
        return $dependencies;
    }
}

class ServiceProvider {
    public static function register() {
        $container = Container::getInstance();
        
        $container->singleton('Database', function() {
            return Database::getInstance();
        });
        
        $container->singleton('Logger', function() {
            return Logger::getInstance();
        });
        
        $container->singleton('SessionManager', function() {
            return new SessionManager();
        });
        
        $container->singleton('DatabaseOptimizer', function() {
            return DatabaseOptimizer::getInstance();
        });
        
        $container->singleton('PerformanceMonitor', function() {
            return new PerformanceMonitor();
        });
        
        $container->singleton('ModernMonitoringSystem', function() {
            return ModernMonitoringSystem::getInstance();
        });
        
        $container->singleton('CSRFProtection', function() {
            return new CSRFProtection();
        });
        
        $container->singleton('InputValidator', function() {
            return new InputValidator();
        });
    }
}

function app($abstract = null, $parameters = []) {
    $container = Container::getInstance();
    
    if ($abstract === null) {
        return $container;
    }
    
    return $container->make($abstract, $parameters);
}