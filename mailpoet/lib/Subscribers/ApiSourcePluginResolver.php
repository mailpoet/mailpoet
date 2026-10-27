<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\WP\Functions as WPFunctions;

class ApiSourcePluginResolver {
  private const IGNORED_SLUGS = ['mailpoet', 'mailpoet-premium'];
  private const TRACE_LIMIT = 50;
  private const MAX_SLUG_LENGTH = 191;

  /** @var WPFunctions */
  private $wp;

  /** @var string|null */
  private $pluginsDir;

  public function __construct(
    WPFunctions $wp,
    ?string $pluginsDir = null
  ) {
    $this->wp = $wp;
    $this->pluginsDir = $pluginsDir;
  }

  /**
   * Returns the slug of the first third-party plugin or theme found in the current call stack.
   * Meant for API signups; outside of the signup request (e.g. cron) the stack holds no plugin.
   */
  public function resolve(): ?string {
    // phpcs:disable QITStandard.PHP.DebugCode.DebugFunctionFound
    // Some hosts list debug_backtrace in disable_functions; is_callable() detects that at runtime.
    // @phpstan-ignore-next-line function.alreadyNarrowedType
    if (!function_exists('debug_backtrace') || !is_callable('debug_backtrace')) {
      return null;
    }
    $files = array_column(
      debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::TRACE_LIMIT), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- the call stack is the only source of the calling plugin.
      'file'
    );
    // phpcs:enable QITStandard.PHP.DebugCode.DebugFunctionFound
    return $this->resolveFromFiles($files);
  }

  /**
   * @param string[] $files
   */
  public function resolveFromFiles(array $files): ?string {
    $pluginsDirs = $this->getDirs(
      $this->pluginsDir ?? (defined('WP_PLUGIN_DIR') ? (string)constant('WP_PLUGIN_DIR') : null)
    );
    $themesDirs = $this->getActiveThemeDirs();
    foreach ($files as $file) {
      $normalizedFile = $this->normalizePath($file);
      $slug = null;
      foreach ($pluginsDirs as $pluginsDir) {
        $slug = $this->getSlug($normalizedFile, $pluginsDir);
        if ($slug) {
          break;
        }
      }
      $slug = $slug ?: $this->getThemeSlug($normalizedFile, $themesDirs);
      // Backtrace paths are real paths, so they don't match a plugin symlinked into the plugins directory.
      // plugin_basename() knows about those through wp_register_plugin_realpath().
      $slug = $slug ?: $this->getSlugFromPluginBasename($normalizedFile);
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
    return $this->getSlugFromRelativePath(substr($file, strlen($prefix)));
  }

  /**
   * @param string[] $themesDirs theme directory => theme slug
   */
  private function getThemeSlug(string $file, array $themesDirs): ?string {
    foreach ($themesDirs as $dir => $slug) {
      if (strpos($file, $dir . '/') === 0) {
        return $slug;
      }
    }
    return null;
  }

  /**
   * Only the active theme and its parent can run code, so there is no need to scan all theme roots.
   * That also covers themes in directories added by register_theme_directory().
   * The slug is the theme folder name (e.g. Divi), which is what the theme directory ends with.
   * @return string[] theme directory (also its real path, as backtraces hold real paths) => theme slug
   */
  private function getActiveThemeDirs(): array {
    $result = [];
    foreach ([$this->wp->getStylesheetDirectory(), $this->wp->getTemplateDirectory()] as $themeDir) {
      $themeDir = (string)$themeDir;
      $slug = basename($this->normalizePath($themeDir));
      if ($slug === '' || strlen($slug) > self::MAX_SLUG_LENGTH) {
        continue;
      }
      foreach ($this->getDirs($themeDir) as $dir) {
        $result[$dir] = $slug;
      }
    }
    return $result;
  }

  private function getSlugFromPluginBasename(string $file): ?string {
    $muPluginsDir = defined('WPMU_PLUGIN_DIR') ? rtrim($this->normalizePath((string)constant('WPMU_PLUGIN_DIR')), '/') : null;
    if ($muPluginsDir && strpos($file, $muPluginsDir . '/') === 0) {
      return null;
    }
    $basename = $this->wp->pluginBasename($file);
    // plugin_basename() returns the path unchanged when the file is not inside a plugin directory.
    // It also trims slashes, so compare without the leading one
    $basename = ltrim($this->normalizePath((string)$basename), '/');
    if ($basename === '' || $basename === ltrim($file, '/')) {
      return null;
    }
    return $this->getSlugFromRelativePath($basename);
  }

  private function getSlugFromRelativePath(string $relativePath): ?string {
    $slug = explode('/', $relativePath)[0];
    // Single file plugins live directly in the plugins directory
    if ($slug === $relativePath && substr($slug, -4) === '.php') {
      $slug = substr($slug, 0, -4);
    }
    return $slug && strlen($slug) <= self::MAX_SLUG_LENGTH ? $slug : null;
  }

  /**
   * Backtrace paths are real paths, so besides the configured directory
   * the resolved one is needed when the directory is a symlink.
   * @return string[]
   */
  private function getDirs(?string $dir): array {
    if (!$dir) {
      return [];
    }
    $dirs = [rtrim($this->normalizePath($dir), '/')];
    $realPath = realpath($dir);
    if ($realPath) {
      $dirs[] = rtrim($this->normalizePath($realPath), '/');
    }
    return array_values(array_unique($dirs));
  }

  private function normalizePath(string $path): string {
    return str_replace('\\', '/', $path);
  }
}
