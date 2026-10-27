<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers;

use MailPoet\Subscribers\ApiSourcePluginResolver;
use MailPoet\WP\Functions as WPFunctions;

class ApiSourcePluginResolverTest extends \MailPoetUnitTest {
  public function testItResolvesFirstThirdPartyPlugin(): void {
    $dir = '/var/www/html/wp-content/plugins';
    $resolver = new ApiSourcePluginResolver($this->createMock(WPFunctions::class), $dir);
    $slug = $resolver->resolveFromFiles([
      $dir . '/mailpoet/lib/API/MP/v1/Subscribers.php',
      $dir . '/mailpoet-premium/lib/Foo.php',
      $dir . '/gravityforms-mailpoet/includes/class-feed.php',
      $dir . '/other-plugin/other.php',
    ]);
    verify($slug)->equals('gravityforms-mailpoet');
  }

  public function testItResolvesSingleFilePlugin(): void {
    $resolver = new ApiSourcePluginResolver($this->createMock(WPFunctions::class), '/var/www/html/wp-content/plugins/');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-content/plugins/hello.php']))->equals('hello');
  }

  public function testItReturnsNullWhenNoThirdPartyPluginIsInTheStack(): void {
    $resolver = new ApiSourcePluginResolver($this->createMock(WPFunctions::class), '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles([
      '/var/www/html/wp-content/plugins/mailpoet/lib/Foo.php',
      '/var/www/html/wp-includes/plugin.php',
      '/var/www/html/wp-content/mu-plugins/loader.php',
    ]))->null();
  }

  public function testItIgnoresFilesOutsidePluginDirectoriesWhenUsingPluginBasename(): void {
    $wp = $this->createMock(WPFunctions::class);
    // Like plugin_basename(), trims the leading slash of paths outside the plugin directories
    $wp->method('pluginBasename')->willReturnCallback(function (string $file): string {
      return trim($file, '/');
    });
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-includes/plugin.php']))->null();
  }

  public function testItResolvesSymlinkedPluginUsingPluginBasename(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('pluginBasename')->willReturn('my-plugin/my-plugin.php');
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles(['/home/dev/my-plugin/my-plugin.php']))->equals('my-plugin');
  }

  public function testItResolvesActiveThemeFolderAsSlug(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getStylesheetDirectory')->willReturn('/var/www/html/wp-content/themes/Divi');
    $wp->method('getTemplateDirectory')->willReturn('/var/www/html/wp-content/themes/Divi');
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles([
      '/var/www/html/wp-content/plugins/mailpoet/lib/Foo.php',
      '/var/www/html/wp-content/themes/Divi/includes/builder/module/Signup.php',
    ]))->equals('Divi');
  }

  public function testItResolvesParentThemeOfChildTheme(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getStylesheetDirectory')->willReturn('/var/www/html/wp-content/themes/divi-child');
    $wp->method('getTemplateDirectory')->willReturn('/var/www/html/wp-content/themes/Divi');
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-content/themes/Divi/includes/Signup.php']))->equals('Divi');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-content/themes/divi-child/functions.php']))->equals('divi-child');
  }

  public function testItResolvesThemeInCustomOrNestedThemeDirectory(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getStylesheetDirectory')->willReturn('/var/www/custom-themes/group/Divi');
    $wp->method('getTemplateDirectory')->willReturn('/var/www/custom-themes/group/Divi');
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles(['/var/www/custom-themes/group/Divi/includes/Signup.php']))->equals('Divi');
  }

  public function testItIgnoresFilesOutsideActiveTheme(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getStylesheetDirectory')->willReturn('/var/www/html/wp-content/themes/Divi');
    $wp->method('getTemplateDirectory')->willReturn('/var/www/html/wp-content/themes/Divi');
    $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-content/themes/DiviOther/Foo.php']))->null();
  }

  public function testItResolvesSymlinkedThemeUsingItsRealPath(): void {
    $base = sys_get_temp_dir() . '/mailpoet-theme-' . uniqid();
    $realTheme = $base . '/real/Divi';
    mkdir($realTheme, 0777, true);
    mkdir($base . '/themes');
    symlink($realTheme, $base . '/themes/Divi');
    try {
      $wp = $this->createMock(WPFunctions::class);
      $wp->method('getStylesheetDirectory')->willReturn($base . '/themes/Divi');
      $wp->method('getTemplateDirectory')->willReturn($base . '/themes/Divi');
      $resolver = new ApiSourcePluginResolver($wp, '/var/www/html/wp-content/plugins');
      // Backtraces hold the real path of the symlink target
      verify($resolver->resolveFromFiles([realpath($realTheme) . '/includes/Signup.php']))->equals('Divi');
    } finally {
      unlink($base . '/themes/Divi');
      rmdir($base . '/themes');
      rmdir($realTheme);
      rmdir($base . '/real');
      rmdir($base);
    }
  }
}
