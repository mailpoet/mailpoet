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
}
