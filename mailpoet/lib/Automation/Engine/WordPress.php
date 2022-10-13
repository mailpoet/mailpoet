<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use WP_User;

class WordPress {
  public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
    return add_action($hookName, $callback, $priority, $acceptedArgs);
  }

  /** @param mixed ...$arg */
  public function doAction(string $hookName, ...$arg): void {
    do_action($hookName, ...$arg);
  }

  public function wpGetCurrentUser(): WP_User {
    return wp_get_current_user();
  }

  /** @param mixed ...$args */
  public function currentUserCan(string $capability, ...$args): bool {
    return current_user_can($capability, ...$args);
  }

  public function registerRestRoute(string $namespace, string $route, array $args = [], bool $override = false): bool {
    return register_rest_route($namespace, $route, $args, $override);
  }
}
