<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor;

class Container {
  protected array $services = [];
  protected array $instances = [];

  public function set(string $name, callable $callable): void {
    $this->services[$name] = $callable;
  }

  public function get(string $name) {
    // Check if the service is already instantiated
    if (isset($this->instances[$name])) {
      return $this->instances[$name];
    }

    // Check if the service is registered
    if (!isset($this->services[$name])) {
      throw new \Exception("Service not found: $name");
    }

    $this->instances[$name] = $this->services[$name]($this);

    return $this->instances[$name];
  }
}
