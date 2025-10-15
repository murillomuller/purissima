<?php

namespace App\Core;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete);
    }

    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            
            if (is_callable($concrete)) {
                $instance = $concrete();
            } elseif (is_string($concrete) && class_exists($concrete)) {
                $instance = new $concrete();
            } else {
                $instance = $concrete;
            }

            $this->instances[$abstract] = $instance;
            return $instance;
        }

        throw new \Exception("No binding found for {$abstract}");
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
