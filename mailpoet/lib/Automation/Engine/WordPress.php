<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use DateTimeZone;
use WP_Comment;
use WP_Error;
use WP_Locale;
use WP_Post;
use WP_Term;
use WP_User;
use wpdb;

class WordPress {
  public function getWpdb(): wpdb {
    global $wpdb;
    return $wpdb;
  }

  public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool {
    return add_action($hookName, $callback, $priority, $acceptedArgs);
  }

  /** @param mixed ...$arg */
  public function doAction(string $hookName, ...$arg): void {
    do_action($hookName, ...$arg);
  }

  /**
   * @param mixed $value
   * @param mixed ...$args
   * @return mixed
   */
  public function applyFilters(string $hookName, $value, ...$args) {
    return apply_filters($hookName, $value, ...$args);
  }

  public function wpTimezone(): DateTimeZone {
    return wp_timezone();
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

  public function getWpLocale(): WP_Locale {
    global $wp_locale;
    return $wp_locale;
  }

  /** @return WP_Post[]|int[] */
  public function getPosts(array $args = null): array {
    return get_posts($args);
  }

  /**
   * @param string|array $args
   * @return WP_Comment[]|int[]|int
   */
  public function getComments($args = '') {
    return get_comments($args);
  }

  /**
   * @param array|string $args
   * @param array|string $deprecated
   * @return WP_Term[]|int[]|string[]|string|WP_Error
   */
  public function getTerms($args = [], $deprecated = '') {
    return get_terms($args, $deprecated);
  }
}
