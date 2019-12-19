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
    $queue->newsletter_id = $newsletter->id;
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $this->queue = $queue->save();
  }

  public function testItGetsArchives() {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    WordPress::interceptFunction('apply_filters', function() use($shortcodes) {
      $args = func_get_args();
      $filter_name = array_shift($args);
      switch ($filter_name) {
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
    $link = $link->attr('href');
    expect($link)->contains('endpoint=view_in_browser');
    // request data object contains newsletter hash but not newsletter id
    $parsed_link = parse_url($link);
    parse_str(html_entity_decode($parsed_link['query']), $data);
    $request_data = Url::transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
    expect($request_data['newsletter_id'])->isEmpty();
    expect($request_data['newsletter_hash'])->equals($this->newsletter->hash);
  }

  public function testItDisplaysManageSubscriptionFormForLoggedinExistingUsers() {
    $wp_user = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->email = $wp_user->data->user_email;
    $subscriber->wp_user_id = $wp_user->ID;
    $subscriber->save();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->contains('form method="POST"');
    expect($result)->contains($subscriber->email);
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedinNonexistentSubscribers() {
    $wp_user = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    expect(Subscriber::findOne($wp_user->data->user_email))->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->contains('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedOutUsers() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->contains('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDisplaysLinkToManageSubscriptionPageForLoggedinExistingUsers() {
    $wp_user = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->email = $wp_user->data->user_email;
    $subscriber->wp_user_id = $wp_user->ID;
    $subscriber->save();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->contains('Manage your subscription');
  }

  public function testItDoesNotDisplayLinkToManageSubscriptionPageForLoggedinNonexistentSubscribers() {
    $wp_user = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    expect(Subscriber::findOne($wp_user->data->user_email))->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->contains('Link to subscription management page is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionPageForLoggedOutUsers() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->contains('Link to subscription management page is only available to mailing lists subscribers.');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
