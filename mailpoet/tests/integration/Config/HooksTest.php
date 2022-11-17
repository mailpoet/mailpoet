<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\Hooks;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\DI\ContainerWrapper;
use MailPoet\WP\Functions as WPFunctions;

class HooksTest extends \MailPoetTest {
  public function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = ContainerWrapper::getInstance()->get(Hooks::class);
    $hooks->setupPostNotifications();
    expect(has_filter('transition_post_status'))->notEmpty();
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
    $this->assertEquals(true, WPHooksHelper::isActionAdded('shutdown'));
    // manual hook execution and check with mocked return value
    $shutdownHook = WPHooksHelper::getActionAdded('shutdown');
    $this->assertEquals('success', call_user_func($shutdownHook[0]));
  }
}
