<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

class WordPress {
  public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
    return add_action($hookName, $callback, $priority, $acceptedArgs);
  }

  /** @param mixed ...$arg */
  public function doAction(string $hookName, ...$arg): void {
    do_action($hookName, ...$arg);
  }

  /** @param mixed ...$args */
  public function currentUserCan(string $capability, ...$args): bool {
    return current_user_can($capability, ...$args);
  }

  public function registerRestRoute(string $namespace, string $route, array $args = [], bool $override = false): bool {
    return register_rest_route($namespace, $route, $args, $override);
  }
}
