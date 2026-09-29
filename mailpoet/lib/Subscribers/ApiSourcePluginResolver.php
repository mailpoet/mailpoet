<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

class ApiSourcePluginResolver {
  private const IGNORED_SLUGS = ['mailpoet', 'mailpoet-premium'];
  private const TRACE_LIMIT = 50;
  private const MAX_SLUG_LENGTH = 191;

  /** @var string|null */
  private $pluginsDir;

  public function __construct(
    ?string $pluginsDir = null
  ) {
    $this->pluginsDir = $pluginsDir;
  }

  /**
   * Returns the slug of the first third-party plugin found in the current call stack.
   * Meant for API signups; outside of the signup request (e.g. cron) the stack holds no plugin.
   */
  public function resolve(): ?string {
    $files = array_column(
      debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::TRACE_LIMIT), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- the call stack is the only source of the calling plugin.
      'file'
    );
    return $this->resolveFromFiles($files);
  }

  /**
   * @param string[] $files
   */
  public function resolveFromFiles(array $files): ?string {
    $pluginsDir = $this->getPluginsDir();
    if (!$pluginsDir) {
      return null;
    }
    foreach ($files as $file) {
      $slug = $this->getSlug($this->normalizePath($file), $pluginsDir);
      if ($slug && !in_array($slug, self::IGNORED_SLUGS, true)) {
        return $slug;
      }
    }
    return null;
  }

  private function getSlug(string $file, string $pluginsDir): ?string {
    $prefix = $pluginsDir . '/';
    if (strpos($file, $prefix) !== 0) {
      return null;
    }
    $relativePath = substr($file, strlen($prefix));
    $slug = explode('/', $relativePath)[0];
    // Single file plugins live directly in the plugins directory
    if ($slug === $relativePath && substr($slug, -4) === '.php') {
      $slug = substr($slug, 0, -4);
    }
    return $slug && strlen($slug) <= self::MAX_SLUG_LENGTH ? $slug : null;
  }

  private function getPluginsDir(): ?string {
    $pluginsDir = $this->pluginsDir ?? (defined('WP_PLUGIN_DIR') ? (string)constant('WP_PLUGIN_DIR') : null);
    return $pluginsDir ? rtrim($this->normalizePath($pluginsDir), '/') : null;
  }

  private function normalizePath(string $path): string {
    return str_replace('\\', '/', $path);
  }
}
