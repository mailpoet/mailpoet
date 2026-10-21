<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Initializer;

class InitializerTest extends \MailPoetTest {
  /** @var string|null */
  private $originalRequestUri;

  /** @var array */
  private $originalGet;

  public function _before() {
    parent::_before();
    $uri = $_SERVER['REQUEST_URI'] ?? null;
    $this->originalRequestUri = is_string($uri) ? $uri : null;
    $this->originalGet = $_GET;
  }

  public function _after() {
    delete_option(Initializer::PLUGIN_ACTIVATED);
    remove_filter('wp_doing_ajax', '__return_true');
    if (function_exists('set_current_screen')) {
      set_current_screen('front');
    }
    if ($this->originalRequestUri === null) {
      unset($_SERVER['REQUEST_URI']);
    } else {
      $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
    }
    $_GET = $this->originalGet;
    parent::_after();
  }

  public function testItConfiguresHooks() {
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $isHooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach ($wp_filter['wp_loaded'][10] as $name => $hook) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      if (preg_match('/postInitialize/', $name)) $isHooked = true;
    }
    verify($isHooked)->true();
  }

  public function testActivationFlagSurvivesAdminAjaxRequests() {
    if (!defined(Initializer::INITIALIZED)) define(Initializer::INITIALIZED, true);
    $initializer = $this->diContainer->get(Initializer::class);
    add_option(Initializer::PLUGIN_ACTIVATED, true);
    if (!function_exists('set_current_screen')) {
      require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
      require_once ABSPATH . 'wp-admin/includes/screen.php';
    }
    set_current_screen('plugins');

    $_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
    add_filter('wp_doing_ajax', '__return_true');
    $initializer->afterPluginActivation();
    verify(get_option(Initializer::PLUGIN_ACTIVATED))->notEmpty();

    remove_filter('wp_doing_ajax', '__return_true');
    $_SERVER['REQUEST_URI'] = '/wp-admin/index.php';
    $initializer->afterPluginActivation();
    verify(get_option(Initializer::PLUGIN_ACTIVATED))->empty();
  }
}
