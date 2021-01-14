<?php

namespace MailPoet\Test\Config;

use Codeception\Util\Fixtures;
use Helper\WordPress;
use MailPoet\Config\Shortcodes;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class ShortcodesTest extends \MailPoetTest {
  public $queue;
  public $newsletter;

  public function _before() {
    parent::_before();
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->status = Newsletter::STATUS_SENT;
    $this->newsletter = $newsletter->save();
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $this->queue = $queue->save();
  }

  public function testItGetsArchives() {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    WordPress::interceptFunction('apply_filters', function() use($shortcodes) {
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_subject':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    // result contains a link pointing to the "view in browser" router endpoint
    $result = $shortcodes->getArchive($params = false);
    WordPress::releaseFunction('apply_filters');
    $dom = \pQuery::parseStr($result);
    $link = $dom->query('a');
    /** @var string $link */
    $link = $link->attr('href');
    expect($link)->stringContainsString('endpoint=view_in_browser');
    $parsedLink = parse_url($link, PHP_URL_QUERY);
    parse_str(html_entity_decode((string)$parsedLink), $data);
    $requestData = Url::transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
    expect($requestData['newsletter_hash'])->equals($this->newsletter->hash);
  }

  public function testItDisplaysManageSubscriptionFormForLoggedinExistingUsers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->email = $wpUser->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $subscriber->wpUserId = $wpUser->ID;
    $subscriber->save();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->stringContainsString('form class="mailpoet-manage-subscription" method="post"');
    expect($result)->stringContainsString($subscriber->email);
  }

  public function testItAppliesFilterForManageSubscriptionForm() {
    $wpUser = wp_set_current_user(1);
    $wp = new WPFunctions;
    expect($wp->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->email = $wpUser->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $subscriber->wpUserId = $wpUser->ID;
    $subscriber->save();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();

    $wp->addAction('mailpoet_manage_subscription_page', function ($page) {
      return $page . ' MY CUSTOM CONTENT';
    });
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->stringContainsString('form class="mailpoet-manage-subscription" method="post"');
    expect($result)->stringContainsString('MY CUSTOM CONTENT');
    $wp->removeAllActions('mailpoet_manage_subscription_page');
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedinNonexistentSubscribers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    expect(Subscriber::findOne($wpUser->user_email))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->stringContainsString('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedOutUsers() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->stringContainsString('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDisplaysLinkToManageSubscriptionPageForLoggedinExistingUsers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->email = $wpUser->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $subscriber->wpUserId = $wpUser->ID;
    $subscriber->save();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->stringContainsString('Manage your subscription');
  }

  public function testItDoesNotDisplayLinkToManageSubscriptionPageForLoggedinNonexistentSubscribers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    expect(Subscriber::findOne($wpUser->user_email))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->stringContainsString('Link to subscription management page is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionPageForLoggedOutUsers() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->stringContainsString('Link to subscription management page is only available to mailing lists subscribers.');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
