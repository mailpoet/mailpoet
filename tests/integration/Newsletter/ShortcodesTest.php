<?php

namespace MailPoet\Test\Newsletter;

use MailPoet\Config\Populator;
use MailPoet\Form\FormsRepository;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

require_once(ABSPATH . 'wp-admin/includes/user.php');

class ShortcodesTest extends \MailPoetTest {
  public $newsletterId;
  public $wPPost;
  public $wPUser;
  public $renderedNewsletter;
  public $newsletter;
  public $subscriber;
  /** @var Shortcodes */
  private $shortcodesObject;
  /** @var SettingsController */
  private $settings;
  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $referralDetector = new ReferralDetector(WPFunctions::get(), $this->settings);
    $populator = new Populator(
      $this->settings,
      WPFunctions::get(),
      new Captcha,
      $referralDetector,
      $this->diContainer->get(FormsRepository::class)
    );
    $populator->up();
    $this->wPUser = $this->_createWPUser();
    $this->wPPost = $this->_createWPPost();
    $this->subscriber = $this->_createSubscriber();
    $this->newsletter = $this->_createNewsletter();
    $this->shortcodesObject = new Shortcodes(
      $this->newsletter,
      $this->subscriber
    );
    $this->settings->set('tracking.enabled', false);
    $this->subscriptionUrlFactory = new SubscriptionUrlFactory(WPFunctions::get(), $this->settings, new LinkTokens);
  }

  public function testItCanExtractShortcodes() {
    $content = '[category:action] [notshortcode]';
    $shortcodes = $this->shortcodesObject->extract($content);
    expect(count($shortcodes))->equals(1);
  }

  public function testItCanExtractOnlySelectShortcodes() {
    $content = '[link:action] [newsletter:action]';
    $limit = ['link'];
    $shortcodes = $this->shortcodesObject->extract($content, $limit);
    expect(count($shortcodes))->equals(1);
    expect(preg_match('/link/', $shortcodes[0]))->equals(1);
  }

  public function testItCanMatchShortcodeDetails() {
    $shortcodesObject = $this->shortcodesObject;
    $content = '[category:action]';
    $details = $shortcodesObject->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    $content = '[category:action|default:default_value]';
    $details = $shortcodesObject->match($content);
    expect($details['category'])->equals('category');
    expect($details['action'])->equals('action');
    expect($details['argument'])->equals('default');
    expect($details['argument_value'])->equals('default_value');
    $content = '[category:action|default]';
    $details = $shortcodesObject->match($content);
    expect($details)->isEmpty();
    $content = '[category|default:default_value]';
    $details = $shortcodesObject->match($content);
    expect($details)->isEmpty();
  }

  public function testItCanProcessCustomShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $shortcode = ['[some:shortcode]'];
    $result = $shortcodesObject->process($shortcode);
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode', function(
      $shortcode, $newsletter, $subscriber, $queue, $content) {
      if ($shortcode === '[some:shortcode]') return 'success';
    }, 10, 5);
    $result = $shortcodesObject->process($shortcode);
    expect($result[0])->equals('success');
  }

  public function testItCanProcessDateShortcodes() {
    $shortcodeDetails = ['action' => 'd'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('d', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'dordinal'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('jS', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'dtext'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('l', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'm'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('m', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'mtext'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('F', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'y'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('Y', WPFunctions::get()->currentTime('timestamp')));
    // allow custom date formats (http://php.net/manual/en/function.date.php)
    $shortcodeDetails = ['action' => 'custom', 'action_argument' => 'format', 'action_argument_value' => 'U F'];
    expect(Date::process($shortcodeDetails))->equals(date_i18n('U F', WPFunctions::get()->currentTime('timestamp')));
  }

  public function testItCanProcessNewsletterShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $content =
      '<a data-post-id="' . $this->wPPost . '" href="#">latest post</a>' .
      '<a data-post-id="10" href="#">another post</a>' .
      '<a href="#">not post</a>';
    $result =
      $shortcodesObject->process(['[newsletter:subject]'], $content);
    expect($result[0])->equals($this->newsletter->subject);
    $result =
      $shortcodesObject->process(['[newsletter:total]'], $content);
    expect($result[0])->equals(2);
    $result =
      $shortcodesObject->process(['[newsletter:post_title]'], $content);
    $wpPost = get_post($this->wPPost);
    expect($result['0'])->equals($wpPost->post_title); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function itCanProcessPostNotificationNewsletterNumberShortcode() {
    // create first post notification
    $postNotificationHistory = $this->_createNewsletter(
      $parentId = $this->newsletterId,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $postNotificationHistory,
      $this->subscriber
    );
    $result = $shortcodesObject->process(['[newsletter:number]']);
    expect($result['0'])->equals(1);

    // create another post notification
    $postNotificationHistory = $this->_createNewsletter(
      $parentId = $this->newsletterId,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $postNotificationHistory,
      $this->subscriber
    );
    $result = $shortcodesObject->process(['[newsletter:number]']);
    expect($result['0'])->equals(2);
  }

  public function testSubscriberShortcodesRequireSubscriberObjectOrFalseValue() {
    // when subscriber is empty, default value is returned
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber = false
    );
    $result = $shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('test');
    // when subscriber is an object, proper value is returned
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $this->subscriber
    );
    $result = $shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals($this->subscriber->first_name);
    // when subscriber is not empty and not an object, shortcode is returned
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber = []
    );
    $result = $shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('[subscriber:firstname | default:test]');
  }

  public function testSubscriberFirstAndLastNameShortcodesReturnDefaultValueWhenDataIsEmpty() {
    // when subscriber exists but first or last names are empty, default value is returned
    $subscriber = $this->subscriber;
    $subscriber->firstName = '';
    $subscriber->lastName = '';
    $shortcodesObject = new \MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $subscriber
    );
    $result = $shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('test');
    $result = $shortcodesObject->process(['[subscriber:lastname | default:test]']);
    expect($result[0])->equals('test');
  }

  public function testItCanProcessSubscriberShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $result =
      $shortcodesObject->process(['[subscriber:firstname]']);
    expect($result[0])->equals($this->subscriber->first_name);
    $result =
      $shortcodesObject->process(['[subscriber:lastname]']);
    expect($result[0])->equals($this->subscriber->last_name);
    $result =
      $shortcodesObject->process(['[subscriber:displayname]']);
    expect($result[0])->equals($this->wPUser->user_login);
    $subscribers = Subscriber::where('status', 'subscribed')
      ->findMany();
    $subscriberCount = count($subscribers);
    $result =
      $shortcodesObject->process(['[subscriber:count]']);
    expect($result[0])->equals($subscriberCount);
    $this->subscriber->status = 'unsubscribed';
    $this->subscriber->save();
    $result =
      $shortcodesObject->process(['[subscriber:count]']);
    expect($result[0])->equals($subscriberCount - 1);
    $this->subscriber->status = 'bounced';
    $this->subscriber->save();
    $result =
      $shortcodesObject->process(['[subscriber:count]']);
    expect($result[0])->equals($subscriberCount - 1);
  }

  public function testItCanProcessSubscriberCustomFieldShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $subscriber = $this->subscriber;
    $customField = CustomField::create();
    $customField->name = 'custom_field_name';
    $customField->type = 'text';
    $customField->save();
    $result = $shortcodesObject->process(
      ['[subscriber:cf_' . $customField->id . ']']
    );
    expect($result[0])->false();
    $subscriberCustomField = SubscriberCustomField::create();
    $subscriberCustomField->subscriberId = $subscriber->id;
    $subscriberCustomField->customFieldId = (int)$customField->id;
    $subscriberCustomField->value = 'custom_field_value';
    $subscriberCustomField->save();
    $result = $shortcodesObject->process(
      ['[subscriber:cf_' . $customField->id . ']']
    );
    expect($result[0])->equals($subscriberCustomField->value);
  }

  public function testItCanProcessLinkShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $result =
      $shortcodesObject->process(['[link:subscription_unsubscribe_url]']);
    expect($result['0'])->regExp('/^http.*?action=confirm_unsubscribe/');
    $result =
      $shortcodesObject->process(['[link:subscription_instant_unsubscribe_url]']);
    expect($result['0'])->regExp('/^http.*?action=unsubscribe/');
    $result =
      $shortcodesObject->process(['[link:subscription_manage_url]']);
    expect($result['0'])->regExp('/^http.*?action=manage/');
    $result =
    $result =
      $shortcodesObject->process(['[link:newsletter_view_in_browser_url]']);
    expect($result['0'])->regExp('/^http.*?endpoint=view_in_browser/');
  }

  public function testItReturnsShortcodeWhenTrackingEnabled() {
    $shortcodesObject = $this->shortcodesObject;
    // Returns URL when tracking is not enabled
    $shortcode = '[link:subscription_unsubscribe_url]';
    $result =
      $shortcodesObject->process([$shortcode]);
    expect($result['0'])->regExp('/^http.*?action=confirm_unsubscribe/');
    // Returns shortcodes when tracking enabled
    $this->settings->set('tracking.enabled', true);
    $initialShortcodes = [
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_instant_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]',
    ];
    $expectedTransformedShortcodes = [
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_instant_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]',
    ];
    // tracking function only works during sending, so queue object must not be false
    $shortcodesObject->queue = true;
    $result = $shortcodesObject->process($initialShortcodes);
    foreach ($result as $index => $transformedShortcode) {
      // 1. result must not contain a link
      expect($transformedShortcode)->regExp('/^((?!href="http).)*$/');
      // 2. result must include a URL shortcode. for example:
      // [link:subscription_unsubscribe] should become
      // [link:subscription_unsubscribe_url]
      expect($transformedShortcode)
        ->regExp('/' . preg_quote($expectedTransformedShortcodes[$index]) . '/');
    }
  }

  public function testItReturnsDefaultLinksWhenPreviewIsEnabled() {
    $shortcodesObject = $this->shortcodesObject;
    $shortcodesObject->wpUserPreview = true;
    $shortcodes = [
      '[link:subscription_unsubscribe_url]',
      '[link:subscription_instant_unsubscribe_url]',
      '[link:subscription_manage_url]',
      '[link:newsletter_view_in_browser_url]',
    ];
    $links = [
      $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl(null),
      $this->subscriptionUrlFactory->getUnsubscribeUrl(null),
      $this->subscriptionUrlFactory->getManageUrl(null),
      NewsletterUrl::getViewInBrowserUrl($this->newsletter),
    ];
    $result = $shortcodesObject->process($shortcodes);
    // hash is returned
    foreach ($result as $index => $transformedShortcode) {
      expect($transformedShortcode)->equals($links[$index]);
    }
  }

  public function testItCanProcessCustomLinkShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $shortcode = '[link:shortcode]';
    $result = $shortcodesObject->process([$shortcode]);
    expect($result[0])->false();
    add_filter('mailpoet_newsletter_shortcode_link', function(
      $shortcode, $newsletter, $subscriber, $queue) {
      if ($shortcode === '[link:shortcode]') return 'success';
    }, 10, 4);
    $result = $shortcodesObject->process([$shortcode]);
    expect($result[0])->equals('success');
    $this->settings->set('tracking.enabled', true);
    // tracking function only works during sending, so queue object must not be false
    $shortcodesObject->queue = true;
    $result = $shortcodesObject->process([$shortcode]);
    expect($result[0])->equals($shortcode);
  }

  public function _createWPPost() {
    $data = [
      'post_title' => 'Sample Post',
      'post_content' => 'contents',
      'post_status' => 'publish',
    ];
    return wp_insert_post($data);
  }

  public function _createWPUser() {
    $wPUser = wp_create_user('phoenix_test_user', 'pass', 'phoenix@test.com');
    $wPUser = get_user_by('login', 'phoenix_test_user');
    return $wPUser;
  }

  public function _createSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      [
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        'email' => 'mister@trump.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'WP_user_id' => $this->wPUser->ID,
      ]
    );
    $subscriber->save();
    return Subscriber::findOne($subscriber->id);
  }

  public function _createNewsletter($parentId = null, $type = Newsletter::TYPE_NOTIFICATION) {
    $newsletter = Newsletter::create();
    $newsletter->hydrate(
      [
        'subject' => 'some subject',
        'type' => $type,
        'status' => Newsletter::STATUS_SENT,
        'parent_id' => $parentId,
      ]
    );
    $newsletter->save();
    return Newsletter::findOne($newsletter->id);
  }

  public function _createQueue() {
    $queue = SendingQueue::create();
    $queue->newsletterId = $this->newsletter['id'];
    $queue->status = 'completed';
    $queue->save();
    return SendingQueue::findOne($queue->id);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
    wp_delete_post($this->wPPost, true);
    wp_delete_user($this->wPUser->ID);
  }
}
