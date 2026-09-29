<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers;

use MailPoet\Subscribers\ApiSourcePluginResolver;

class ApiSourcePluginResolverTest extends \MailPoetUnitTest {
  public function testItResolvesFirstThirdPartyPlugin(): void {
    $dir = '/var/www/html/wp-content/plugins';
    $resolver = new ApiSourcePluginResolver($dir);
    $slug = $resolver->resolveFromFiles([
      $dir . '/mailpoet/lib/API/MP/v1/Subscribers.php',
      $dir . '/mailpoet-premium/lib/Foo.php',
      $dir . '/gravityforms-mailpoet/includes/class-feed.php',
      $dir . '/other-plugin/other.php',
    ]);
    verify($slug)->equals('gravityforms-mailpoet');
  }

  public function testItResolvesSingleFilePlugin(): void {
    $resolver = new ApiSourcePluginResolver('/var/www/html/wp-content/plugins/');
    verify($resolver->resolveFromFiles(['/var/www/html/wp-content/plugins/hello.php']))->equals('hello');
  }

  public function testItReturnsNullWhenNoThirdPartyPluginIsInTheStack(): void {
    $resolver = new ApiSourcePluginResolver('/var/www/html/wp-content/plugins');
    verify($resolver->resolveFromFiles([
      '/var/www/html/wp-content/plugins/mailpoet/lib/Foo.php',
      '/var/www/html/wp-includes/plugin.php',
      '/var/www/html/wp-content/mu-plugins/loader.php',
    ]))->null();
  }
}
