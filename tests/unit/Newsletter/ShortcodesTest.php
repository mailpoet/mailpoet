<?php

use MailPoet\Config\Populator;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Date;

require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/user.php');

class ShortcodesTest extends MailPoetTest {
  public $rendered_newsletter;
  public $newsletter;
  public $subscriber;

  function _before() {
    $populator = new Populator();
    $populator->up();
    $this->WP_user = $this->_createWPUser();
    $this->WP_post = $this->_createWPPost();
    $this->subscriber = $this->_createSubscriber();
    $this->newsletter = array(
      'subject' => 'some subject',
      'type' => 'notification',
      'id' => 2
    );
    $this->shortcodes_object = new MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $this->subscriber
    );
    Setting::setValue('tracking.enabled', false);
  }

  function testItCanExtractShortcodes() {
    $content = '[category:action] [notshortcode]';
    $shortcodes = $this->shortcodes_object->extract($content);
    expect(count($shortcodes))->equals(1);
  }

  function testItCanExtractOnlySelectShortcodes() {
    $content = '[link:action] [newsletter:action]';
    $limit = array('link');
    $shortcodes = $this->shortcodes_object->extract($content, $limit);
    expect(count($shortcodes))->equals(1);
    expect(preg_match('/link/', $shortcodes[0]))->equals(1);
  }

  function testItCanMatchShortcodeDetails() {
    $content = '[category:action]';
    $details = $this->shortcodes_object->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    $content = '[category:action|default:default_value]';
    $details = $this->shortcodes_object->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    expect($details['default'])->equals('default_value');
    $content = '[category:action|default]';
    $details = $this->shortcodes_object->match($content);
    expect($details)->isEmpty();
    $content = '[category|default:default_value]';
    $details = $this->shortcodes_object->match($content);
    expect($details)->isEmpty();
  }

  function testItCanProcessCustomShortcodes() {
    $shortcode = array('[some:shortcode]');
    $result = $this->shortcodes_object->process($shortcode);
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode', function (
      $shortcode, $newsletter, $subscriber, $queue, $content) {
      if($shortcode === '[some:shortcode]') return 'success';
    }, 10, 5);
    $result = $this->shortcodes_object->process($shortcode);
    expect($result[0])->equals('success');
  }

  function testItCanProcessDateShortcodes() {
    $date = new \DateTime('now');
    expect(Date::process('d'))->equals($date->format('d'));
    expect(Date::process('dordinal'))->equals($date->format('dS'));
    expect(Date::process('dtext'))->equals($date->format('D'));
    expect(Date::process('m'))->equals($date->format('m'));
    expect(Date::process('mtext'))->equals($date->format('F'));
    expect(Date::process('y'))->equals($date->format('Y'));
  }

  function testItCanProcessNewsletterShortcodes() {
    $content =
      '<a data-post-id="' . $this->WP_post . '" href="#">latest post</a>' .
      '<a data-post-id="10" href="#">another post</a>' .
      '<a href="#">not post</a>';
    $result =
      $this->shortcodes_object->process(array('[newsletter:subject]'));
    expect($result[0])->equals($this->newsletter['subject']);
    $result =
      $this->shortcodes_object->process(array('[newsletter:total]'), $content);
    expect($result[0])->equals(2);
    $result =
      $this->shortcodes_object->process(array('[newsletter:post_title]'));
    $wp_post = get_post($this->WP_post);
    expect($result['0'])->equals($wp_post->post_title);
    $result =
      $this->shortcodes_object->process(array('[newsletter:number]'));
    expect($result['0'])->equals(1);
    $queue = $this->_createQueue();
    $result =
      $this->shortcodes_object->process(array('[newsletter:number]'));
    expect($result['0'])->equals(2);
  }

  function testItCanProcessUserShortcodes() {
    $result =
      $this->shortcodes_object->process(array('[user:firstname]'));
    expect($result[0])->equals($this->subscriber->first_name);
    $result =
      $this->shortcodes_object->process(array('[user:lastname]'));
    expect($result[0])->equals($this->subscriber->last_name);
    $result =
      $this->shortcodes_object->process(array('[user:displayname]'));
    expect($result[0])->equals($this->WP_user->user_login);
    $subscribers = Subscriber::where('status', 'subscribed')
      ->findMany();
    $subscriber_count = count($subscribers);
    $result =
      $this->shortcodes_object->process(array('[user:count]'));
    expect($result[0])->equals($subscriber_count);
    $this->subscriber->status = 'unsubscribed';
    $this->subscriber->save();
    $result =
      $this->shortcodes_object->process(array('[user:count]'));
    expect($result[0])->equals(--$subscriber_count);
  }

  function testItCanProcessLinkShortcodes() {
    $result =
      $this->shortcodes_object->process(array('[link:subscription_unsubscribe]'));
    expect(preg_match('/^<a.*?\/a>$/', $result['0']))->equals(1);
    expect(preg_match('/action=unsubscribe/', $result['0']))->equals(1);
    $result =
      $this->shortcodes_object->process(array('[link:subscription_unsubscribe_url]'));
    expect(preg_match('/^http.*?action=unsubscribe/', $result['0']))->equals(1);
    $result =
      $this->shortcodes_object->process(array('[link:subscription_manage]'));
    expect(preg_match('/^<a.*?\/a>$/', $result['0']))->equals(1);
    expect(preg_match('/action=manage/', $result['0']))->equals(1);
    $result =
      $this->shortcodes_object->process(array('[link:subscription_manage_url]'));
    expect(preg_match('/^http.*?action=manage/', $result['0']))->equals(1);
    $result =
      $this->shortcodes_object->process(array('[link:newsletter_view_in_browser]'));
    expect(preg_match('/^<a.*?\/a>$/', $result['0']))->equals(1);
    expect(preg_match('/endpoint=view_in_browser/', $result['0']))->equals(1);
    $result =
      $this->shortcodes_object->process(array('[link:newsletter_view_in_browser_url]'));
    expect(preg_match('/^http.*?endpoint=view_in_browser/', $result['0']))->equals(1);
  }

  function testItReturnsShortcodeWhenTrackingEnabled() {
    $shortcode = '[link:subscription_unsubscribe_url]';
    $result =
      $this->shortcodes_object->process(array($shortcode));
    expect(preg_match('/^http.*?action=unsubscribe/', $result['0']))->equals(1);
    Setting::setValue('tracking.enabled', true);
    $shortcodes = array(
      '[link:subscription_unsubscribe]',
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_manage]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser]',
      '[link:newsletter_view_in_browser_url]'
    );
    $result =
      $this->shortcodes_object->process($shortcodes);
    // all returned shortcodes must end with url
    $result = join(',', $result);
    expect(substr_count($result, '_url'))->equals(count($shortcodes));
  }

  function testItCanProcessCustomLinkShortcodes() {
    $shortcode = '[link:shortcode]';
    $result = $this->shortcodes_object->process(array($shortcode));
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode_link', function (
      $shortcode, $newsletter, $subscriber, $queue) {
      if($shortcode === '[link:shortcode]') return 'success';
    }, 10, 4);
    $result = $this->shortcodes_object->process(array($shortcode));
    expect($result[0])->equals('success');
    Setting::setValue('tracking.enabled', true);
    $result = $this->shortcodes_object->process(array($shortcode));
    expect($result[0])->equals($shortcode);
  }

  function _createWPPost() {
    $data = array(
      'post_title' => 'Sample Post',
      'post_content' => 'contents',
      'post_status' => 'publish',
    );
    return wp_insert_post($data);
  }

  function _createWPUser() {
    $WP_user = wp_create_user('phoenix_test_user', 'pass', 'phoenix@test.com');
    $WP_user = get_user_by('login', 'phoenix_test_user');
    return $WP_user;
  }

  function _createSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        'email' => 'mister@trump.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'WP_user_id' => $this->WP_user->ID
      )
    );
    $subscriber->save();
    return Subscriber::findOne($subscriber->id);
  }

  function _createQueue() {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $this->newsletter['id'];
    $queue->status = 'completed';
    $queue->save();
    return $queue;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    wp_delete_post($this->WP_post, true);
    wp_delete_user($this->WP_user->ID);
  }
}