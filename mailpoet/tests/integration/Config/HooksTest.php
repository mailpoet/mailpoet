<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub\Expected;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\Env;
use MailPoet\Config\Hooks;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\DI\ContainerWrapper;
use MailPoet\WP\Functions as WPFunctions;

class HooksTest extends \MailPoetTest {
  public function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = ContainerWrapper::getInstance()->get(Hooks::class);
    $hooks->setupPostNotifications();
    verify(has_filter('transition_post_status'))->notEmpty();
  }

  public function testItHooksSubscriberChangesNotifier() {
    $wp = $this->make(new WPFunctions(), [
      'addAction' => asCallable([WPHooksHelper::class, 'addAction']),
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $subscriberChangesNotifier = $this->make(SubscriberChangesNotifier::class, [
      'wp' => $wp,
      'notify' => 'success',
    ]);
    $hooks = $this->getServiceWithOverrides(Hooks::class, [
      'wp' => $wp,
      'subscriberChangesNotifier' => $subscriberChangesNotifier,
    ]);
    $hooks->setupChangeNotifications();

    // check that shutdown hooks was added
    $this->assertTrue(WPHooksHelper::isActionAdded('shutdown'));
    // manual hook execution and check with mocked return value
    $shutdownHook = WPHooksHelper::getActionAdded('shutdown');
    $this->assertEquals('success', call_user_func($shutdownHook[0]));
  }

  public function testItTriggersTheDatabaseUpdateAfterASingleMailPoetUpgrade(): void {
    $hooks = $this->createHooksExpectingLoopback();
    $hooks->triggerDatabaseUpdate(null, ['action' => 'update', 'type' => 'plugin', 'plugin' => Env::$pluginPath]);
  }

  public function testItTriggersTheDatabaseUpdateAfterABulkUpgradeIncludingMailPoet(): void {
    $hooks = $this->createHooksExpectingLoopback();
    $hooks->triggerDatabaseUpdate(null, ['action' => 'update', 'type' => 'plugin', 'bulk' => true, 'plugins' => ['akismet/akismet.php', Env::$pluginPath]]);
  }

  public function testItIgnoresUpgradesOfOtherPlugins(): void {
    $hooks = $this->createHooksExpectingNoLoopback();
    $hooks->triggerDatabaseUpdate(null, ['action' => 'update', 'type' => 'plugin', 'plugins' => ['akismet/akismet.php']]);
  }

  public function testItIgnoresInstallsAndThemeUpdates(): void {
    $hooks = $this->createHooksExpectingNoLoopback();
    $hooks->triggerDatabaseUpdate(null, ['action' => 'install', 'type' => 'plugin', 'plugin' => Env::$pluginPath]);
    $hooks->triggerDatabaseUpdate(null, ['action' => 'update', 'type' => 'theme', 'themes' => ['twentytwentyfive']]);
    $hooks->triggerDatabaseUpdate(null, null);
  }

  public function testItHooksTheTriggerToUpgraderProcessCompleteWithBothArguments(): void {
    $hooks = $this->createHooksExpectingLoopback();
    // other plugins' handlers expect a real WP_Upgrader; $wp_filter is restored after the test
    remove_all_actions('upgrader_process_complete');
    $hooks->triggerDatabaseUpdateAfterPluginUpgrade();
    do_action('upgrader_process_complete', null, ['action' => 'update', 'type' => 'plugin', 'plugin' => Env::$pluginPath]);
  }

  private function createHooksExpectingLoopback(): Hooks {
    $wp = $this->make(new WPFunctions(), [
      'wpRemotePost' => Expected::once(function (string $url, array $args) {
        verify($url)->stringEndsWith('admin-ajax.php');
        verify($args['blocking'])->false();
        verify($args['body'])->equals(['action' => 'mailpoet_token']);
      }),
    ]);
    return $this->getServiceWithOverrides(Hooks::class, ['wp' => $wp]);
  }

  private function createHooksExpectingNoLoopback(): Hooks {
    $wp = $this->make(new WPFunctions(), ['wpRemotePost' => Expected::never()]);
    return $this->getServiceWithOverrides(Hooks::class, ['wp' => $wp]);
  }
}
