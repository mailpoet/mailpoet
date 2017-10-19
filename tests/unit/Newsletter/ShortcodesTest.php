<?php
namespace MailPoet\Test\Newsletter;

use MailPoet\Config\Populator;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscription\Url as SubscriptionUrl;

require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/user.php');

class ShortcodesTest extends \MailPoetTest {
  public $rendered_newsletter;
  public $newsletter;
  public $subscriber;

  function _before() {
    $populator = new Populator();
    $populator->up();
    $this->WP_user = $this->_createWPUser();
    $this->WP_post = $this->_createWPPost();
    $this->subscriber = $this->_createSubscriber();
    $this->newsletter = $this->_createNewsletter();
    $this->shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
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
    $shortcodes_object = $this->shortcodes_object;
    $content = '[category:action]';
    $details = $shortcodes_object->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    $content = '[category:action|default:default_value]';
    $details = $shortcodes_object->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    expect($details['argument'])->equals('default');
    expect($details['argument_value'])->equals('default_value');
    $content = '[category:action|default]';
    $details = $shortcodes_object->match($content);
    expect($details)->isEmpty();
    $content = '[category|default:default_value]';
    $details = $shortcodes_object->match($content);
    expect($details)->isEmpty();
  }

  function testItCanProcessCustomShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $shortcode = array('[some:shortcode]');
    $result = $shortcodes_object->process($shortcode);
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode', function(
      $shortcode, $newsletter, $subscriber, $queue, $content) {
      if($shortcode === '[some:shortcode]') return 'success';
    }, 10, 5);
    $result = $shortcodes_object->process($shortcode);
    expect($result[0])->equals('success');
  }

  function testItCanProcessDateShortcodes() {
    $date = new \DateTime(current_time('mysql'));
    expect(Date::process('d'))->equals(date_i18n('d', current_time('timestamp')));
    expect(Date::process('dordinal'))->equals(date_i18n('dS', current_time('timestamp')));
    expect(Date::process('dtext'))->equals(date_i18n('l', current_time('timestamp')));
    expect(Date::process('m'))->equals(date_i18n('m', current_time('timestamp')));
    expect(Date::process('mtext'))->equals(date_i18n('F', current_time('timestamp')));
    expect(Date::process('y'))->equals(date_i18n('Y', current_time('timestamp')));
    // allow custom date formats (http://php.net/manual/en/function.date.php)
    expect(Date::process('custom', 'format', 'U F'))->equals(date_i18n('U F', current_time('timestamp')));
  }

  function testItCanProcessNewsletterShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $content =
      '<a data-post-id="' . $this->WP_post . '" href="#">latest post</a>' .
      '<a data-post-id="10" href="#">another post</a>' .
      '<a href="#">not post</a>';
    $result =
      $shortcodes_object->process(array('[newsletter:subject]'), $content);
    expect($result[0])->equals($this->newsletter->subject);
    $result =
      $shortcodes_object->process(array('[newsletter:total]'), $content);
    expect($result[0])->equals(2);
    $result =
      $shortcodes_object->process(array('[newsletter:post_title]'), $content);
    $wp_post = get_post($this->WP_post);
    expect($result['0'])->equals($wp_post->post_title);
  }

  function itCanProcessPostNotificationNewsletterNumberShortcode() {
    // create first post notification
    $post_notification_history = $this->_createNewsletter(
      $parent_id = $this->newsletter_id,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $post_notification_history,
      $this->subscriber
    );
    $result = $shortcodes_object->process(array('[newsletter:number]'));
    expect($result['0'])->equals(1);

    // create another post notification
    $post_notification_history = $this->_createNewsletter(
      $parent_id = $this->newsletter_id,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $post_notification_history,
      $this->subscriber
    );
    $result = $shortcodes_object->process(array('[newsletter:number]'));
    expect($result['0'])->equals(2);
  }

  function testSubscriberShortcodesRequireSubscriberObjectOrFalseValue() {
    // when subscriber is empty, default value is returned
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber = false
    );
    $result = $shortcodes_object->process(array('[subscriber:firstname | default:test]'));
    expect($result[0])->equals('test');
    // when subscriber is an object, proper value is returned
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $this->subscriber
    );
    $result = $shortcodes_object->process(array('[subscriber:firstname | default:test]'));
    expect($result[0])->equals($this->subscriber->first_name);
    // when subscriber is not empty and not an object, false is returned
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber = array()
    );
    $result = $shortcodes_object->process(array('[subscriber:firstname | default:test]'));
    expect($result[0])->false();
  }

  function testSubscriberFirstAndLastNameShortcodesReturnDefaultValueWhenDataIsEmpty() {
    // when subscriber exists but first or last names are empty, default value is returned
    $subscriber = $this->subscriber;
    $subscriber->first_name = '';
    $subscriber->last_name = '';
    $shortcodes_object = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber
    );
    $result = $shortcodes_object->process(array('[subscriber:firstname | default:test]'));
    expect($result[0])->equals('test');
    $result = $shortcodes_object->process(array('[subscriber:lastname | default:test]'));
    expect($result[0])->equals('test');
  }

  function testItCanProcessSubscriberShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $result =
      $shortcodes_object->process(array('[subscriber:firstname]'));
    expect($result[0])->equals($this->subscriber->first_name);
    $result =
      $shortcodes_object->process(array('[subscriber:lastname]'));
    expect($result[0])->equals($this->subscriber->last_name);
    $result =
      $shortcodes_object->process(array('[subscriber:displayname]'));
    expect($result[0])->equals($this->WP_user->user_login);
    $subscribers = Subscriber::where('status', 'subscribed')
      ->findMany();
    $subscriber_count = count($subscribers);
    $result =
      $shortcodes_object->process(array('[subscriber:count]'));
    expect($result[0])->equals($subscriber_count);
    $this->subscriber->status = 'unsubscribed';
    $this->subscriber->save();
    $result =
      $shortcodes_object->process(array('[subscriber:count]'));
    expect($result[0])->equals($subscriber_count - 1);
    $this->subscriber->status = 'bounced';
    $this->subscriber->save();
    $result =
      $shortcodes_object->process(array('[subscriber:count]'));
    expect($result[0])->equals($subscriber_count - 1);
  }

  function testItCanProcessSubscriberCustomFieldShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $subscriber = $this->subscriber;
    $custom_field = CustomField::create();
    $custom_field->name = 'custom_field_name';
    $custom_field->type = 'text';
    $custom_field->save();
    $result = $shortcodes_object->process(
      array('[subscriber:cf_' . $custom_field->id . ']')
    );
    expect($result[0])->false();
    $subscriber_custom_field = SubscriberCustomField::create();
    $subscriber_custom_field->subscriber_id = $subscriber->id;
    $subscriber_custom_field->custom_field_id = $custom_field->id;
    $subscriber_custom_field->value = 'custom_field_value';
    $subscriber_custom_field->save();
    $result = $shortcodes_object->process(
      array('[subscriber:cf_' . $custom_field->id . ']')
    );
    expect($result[0])->equals($subscriber_custom_field->value);
  }

  function testItCanProcessLinkShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $result =
      $shortcodes_object->process(array('[link:subscription_unsubscribe_url]'));
    expect($result['0'])->regExp('/^http.*?action=unsubscribe/');
    $result =
      $shortcodes_object->process(array('[link:subscription_manage_url]'));
    expect($result['0'])->regExp('/^http.*?action=manage/');
    $result =
    $result =
      $shortcodes_object->process(array('[link:newsletter_view_in_browser_url]'));
    expect($result['0'])->regExp('/^http.*?endpoint=view_in_browser/');
  }

  function testItReturnsShortcodeWhenTrackingEnabled() {
    $shortcodes_object = $this->shortcodes_object;
    $shortcode = '[link:subscription_unsubscribe_url]';
    $result =
      $shortcodes_object->process(array($shortcode));
    expect($result['0'])->regExp('/^http.*?action=unsubscribe/');
    Setting::setValue('tracking.enabled', true);
    $initial_shortcodes = array(
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]'
    );
    $expected_transformed_shortcodes = array(
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]'
    );
    // tracking function only works during sending, so queue object must not be false
    $shortcodes_object->queue = true;
    $result = $shortcodes_object->process($initial_shortcodes);
    foreach($result as $index => $transformed_shortcode) {
      // 1. result must not contain a link
      expect($transformed_shortcode)->regExp('/^((?!href="http).)*$/');
      // 2. result must include a URL shortcode. for example:
      // [link:subscription_unsubscribe] should become
      // [link:subscription_unsubscribe_url]
      expect($transformed_shortcode)
        ->regExp('/' . preg_quote($expected_transformed_shortcodes[$index]) . '/');
    }
  }

  function testItReturnsDefaultLinksWhenPreviewIsEnabled() {
    $shortcodes_object = $this->shortcodes_object;
    $shortcodes_object->wp_user_preview = true;
    $shortcodes = array(
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]',
    );
    $links = array(
      SubscriptionUrl::getUnsubscribeUrl(false),
      SubscriptionUrl::getManageUrl(false),
      NewsletterUrl::getViewInBrowserUrl(null, $this->newsletter, false, false, true)
    );
    $result = $shortcodes_object->process($shortcodes);
    // hash is returned
    foreach($result as $index => $transformed_shortcode) {
      expect($transformed_shortcode)->equals($links[$index]);
    }
  }

  function testItCanProcessCustomLinkShortcodes() {
    $shortcodes_object = $this->shortcodes_object;
    $shortcode = '[link:shortcode]';
    $result = $shortcodes_object->process(array($shortcode));
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode_link', function(
      $shortcode, $newsletter, $subscriber, $queue) {
      if($shortcode === '[link:shortcode]') return 'success';
    }, 10, 4);
    $result = $shortcodes_object->process(array($shortcode));
    expect($result[0])->equals('success');
    Setting::setValue('tracking.enabled', true);
    // tracking function only works during sending, so queue object must not be false
    $shortcodes_object->queue = true;
    $result = $shortcodes_object->process(array($shortcode));
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

  function _createNewsletter($parent_id = null, $type = Newsletter::TYPE_NOTIFICATION) {
    $newsletter = Newsletter::create();
    $newsletter->hydrate(
      array(
        'subject' => 'some subject',
        'type' => $type,
        'status' => Newsletter::STATUS_SENT,
        'parent_id' => $parent_id,
      )
    );
    $newsletter->save();
    return Newsletter::findOne($newsletter->id);
  }

  function _createQueue() {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $this->newsletter['id'];
    $queue->status = 'completed';
    $queue->save();
    return SendingQueue::findOne($queue->id);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
    wp_delete_post($this->WP_post, true);
    wp_delete_user($this->WP_user->ID);
  }

}
