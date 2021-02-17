<?php

namespace MailPoet\Test\Newsletter;

use MailPoet\Config\Populator;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use WP_Post;

require_once(ABSPATH . 'wp-admin/includes/user.php');

class ShortcodesTest extends \MailPoetTest {
  private $wPPost;
  private $wPUser;
  /** @var NewsletterEntity */
  private $newsletter;
  /** @var SubscriberEntity */
  private $subscriber;
  /** @var Shortcodes */
  private $shortcodesObject;
  /** @var SettingsController */
  private $settings;
  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->settings = SettingsController::getInstance();
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
    $this->wPUser = $this->_createWPUser();
    $this->wPPost = $this->_createWPPost();
    $this->subscriber = $this->_createSubscriber();
    $this->entityManager->persist($this->subscriber);
    $this->newsletter = $this->_createNewsletter();
    $this->entityManager->persist($this->newsletter);
    $this->shortcodesObject = $this->diContainer->get(Shortcodes::class);
    $this->shortcodesObject->setNewsletter($this->newsletter);
    $this->shortcodesObject->setSubscriber($this->subscriber);
    $this->shortcodesObject->setWpUserPreview(false);
    $this->settings->set('tracking.enabled', false);
    $this->subscriptionUrlFactory = new SubscriptionUrlFactory(WPFunctions::get(), $this->settings, new LinkTokens);
    $this->entityManager->flush();
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
    $date = $this->diContainer->get(Date::class);
    $shortcodeDetails = ['action' => 'd'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('d', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'dordinal'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('jS', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'dtext'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('l', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'm'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('m', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'mtext'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('F', WPFunctions::get()->currentTime('timestamp')));
    $shortcodeDetails = ['action' => 'y'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('Y', WPFunctions::get()->currentTime('timestamp')));
    // allow custom date formats (http://php.net/manual/en/function.date.php)
    $shortcodeDetails = ['action' => 'custom', 'action_argument' => 'format', 'action_argument_value' => 'U F'];
    expect($date->process($shortcodeDetails))->equals(date_i18n('U F', WPFunctions::get()->currentTime('timestamp')));
  }

  public function testItCanProcessNewsletterShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $content =
      '<a data-post-id="' . $this->wPPost . '" href="#">latest post</a>' .
      '<a data-post-id="10" href="#">another post</a>' .
      '<a href="#">not post</a>';
    $result =
      $shortcodesObject->process(['[newsletter:subject]'], $content);
    expect($result[0])->equals($this->newsletter->getSubject());
    $result =
      $shortcodesObject->process(['[newsletter:total]'], $content);
    expect($result[0])->equals(2);
    $result =
      $shortcodesObject->process(['[newsletter:post_title]'], $content);
    $wpPost = get_post($this->wPPost);
    assert($wpPost instanceof WP_Post);
    expect($result['0'])->equals($wpPost->post_title); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function itCanProcessPostNotificationNewsletterNumberShortcode() {
    // create first post notification
    $postNotificationHistory = $this->_createNewsletter(
      $this->newsletter,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $this->shortcodesObject->setNewsletter($postNotificationHistory);
    $result = $this->shortcodesObject->process(['[newsletter:number]']);
    expect($result['0'])->equals(1);

    // create another post notification
    $postNotificationHistory = $this->_createNewsletter(
      $this->newsletter,
      $type = Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $this->shortcodesObject->setNewsletter($postNotificationHistory);
    $result = $this->shortcodesObject->process(['[newsletter:number]']);
    expect($result['0'])->equals(2);
  }

  public function testSubscriberShortcodesRequireSubscriberObjectOrFalseValue() {
    // when subscriber is empty, original value is returned
    $this->shortcodesObject->setSubscriber(null);
    $result = $this->shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('[subscriber:firstname | default:test]');
    // when subscriber is an object, proper value is returned
    $this->shortcodesObject->setSubscriber($this->subscriber);
    $result = $this->shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals($this->subscriber->getFirstName());
    // when subscriber hasn't name, the default value is returned
    $this->subscriber->setFirstName('');
    $this->shortcodesObject->setSubscriber($this->subscriber);
    $result = $this->shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('test');
  }

  public function testSubscriberFirstAndLastNameShortcodesReturnDefaultValueWhenDataIsEmpty() {
    // when subscriber exists but first or last names are empty, default value is returned
    $subscriber = $this->subscriber;
    $subscriber->setFirstName('');
    $subscriber->setLastName('');
    $result = $this->shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals('test');
    $result = $this->shortcodesObject->process(['[subscriber:lastname | default:test]']);
    expect($result[0])->equals('test');
  }

  public function testSanitizeName() {
    $subscriber = $this->subscriber;
    $subscriber->setFirstName(' "><img src=x onError=prompt(1)>');
    $subscriber->setLastName(' "><img src=x onError=prompt(2)>');
    $result = $this->shortcodesObject->process(['[subscriber:firstname | default:test]']);
    expect($result[0])->equals(' &quot;&gt;&lt;img src=x onError=prompt(1)&gt;');
    $result = $this->shortcodesObject->process(['[subscriber:lastname | default:test]']);
    expect($result[0])->equals(' &quot;&gt;&lt;img src=x onError=prompt(2)&gt;');
  }

  public function testItCanProcessSubscriberShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $result =
      $shortcodesObject->process(['[subscriber:firstname]']);
    expect($result[0])->equals($this->subscriber->getFirstName());
    $result =
      $shortcodesObject->process(['[subscriber:lastname]']);
    expect($result[0])->equals($this->subscriber->getLastName());
    $result =
      $shortcodesObject->process(['[subscriber:displayname]']);
    expect($result[0])->equals($this->wPUser->user_login);
    $subscribers = Subscriber::whereIn('status', [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_INACTIVE,
    ])->findMany();
    $subscriberCount = count($subscribers);
    $result =
      $shortcodesObject->process(['[subscriber:count]']);
    expect($result[0])->equals($subscriberCount);
    $this->subscriber->setStatus('unsubscribed');
  }

  public function testItCanProcessSubscriberCustomFieldShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $subscriber = $this->subscriber;
    $customField = new CustomFieldEntity();
    $customField->setName('custom_field_name');
    $customField->setType('text');
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $result = $shortcodesObject->process(
      ['[subscriber:cf_' . $customField->getId() . ']']
    );
    expect($result[0])->null();
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, 'custom_field_value');
    $this->entityManager->persist($subscriberCustomField);
    $this->entityManager->flush();
    $result = $shortcodesObject->process(
      ['[subscriber:cf_' . $customField->getId() . ']']
    );
    expect($result[0])->equals($subscriberCustomField->getValue());
  }

  public function testItCanProcessLinkShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $result =
      $shortcodesObject->process(['[link:subscription_unsubscribe_url]']);
    expect($result['0'])->regExp('/^http.*?action=confirm_unsubscribe/');
    $linkData = $this->getLinkData($result['0']);
    expect($linkData['email'])->equals($this->subscriber->getEmail());
    expect($linkData['token'])->equals($this->subscriber->getLinkToken());

    $result =
      $shortcodesObject->process(['[link:subscription_instant_unsubscribe_url]']);
    expect($result['0'])->regExp('/^http.*?action=unsubscribe/');
    $linkData = $this->getLinkData($result['0']);
    expect($linkData['email'])->equals($this->subscriber->getEmail());
    expect($linkData['token'])->equals($this->subscriber->getLinkToken());

    $result =
      $shortcodesObject->process(['[link:subscription_manage_url]']);
    expect($result['0'])->regExp('/^http.*?action=manage/');
    $linkData = $this->getLinkData($result['0']);
    expect($linkData['email'])->equals($this->subscriber->getEmail());
    expect($linkData['token'])->equals($this->subscriber->getLinkToken());

    $result =
      $shortcodesObject->process(['[link:newsletter_view_in_browser_url]']);
    expect($result['0'])->regExp('/^http.*?endpoint=view_in_browser/');
    $linkData = $this->getLinkData($result['0']);
    expect($linkData['newsletter_id'])->equals($this->newsletter->getId());
    expect($linkData['newsletter_hash'])->equals($this->newsletter->getHash());
    expect($linkData['subscriber_token'])->equals($this->subscriber->getLinkToken());
    expect($linkData['subscriber_id'])->equals($this->subscriber->getId());
  }

  private function getLinkData(string $link): array {
    $parsedUrlQuery = parse_url($link, PHP_URL_QUERY);
    $queryData = [];
    parse_str((string)$parsedUrlQuery, $queryData);
    return NewsletterUrl::transformUrlDataObject(json_decode(base64_decode($queryData['data']), true));
  }

  public function testItReturnsShortcodeWhenTrackingEnabled() {
    $shortcodesObject = $this->shortcodesObject;
    $shortcodesObject->setWpUserPreview(false);
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
    $shortcodesObject->setQueue($this->_createQueue());
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
    $shortcodesObject->setWpUserPreview(true);
    $shortcodesObject->setSubscriber(null);
    $newsletterModel = NewsletterModel::where('id', $this->newsletter->getId())->findOne();
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
      NewsletterUrl::getViewInBrowserUrl($newsletterModel),
    ];
    $result = $shortcodesObject->process($shortcodes);
    // hash is returned
    foreach ($result as $index => $transformedShortcode) {
      expect($transformedShortcode)->equals($links[$index]);
    }
  }

  public function testItCanProcessCustomLinkShortcodes() {
    $shortcodesObject = $this->shortcodesObject;
    $shortcodesObject->setWpUserPreview(false);
    $shortcode = '[link:shortcode]';
    $result = $shortcodesObject->process([$shortcode]);
    expect($result[0])->null();
    remove_all_filters('mailpoet_newsletter_shortcode_link');
    add_filter('mailpoet_newsletter_shortcode_link', function($shortcode, $newsletter, $subscriber, $queue) {
      if ($shortcode === '[link:shortcode]') return 'success';
    }, 10, 4);

    $result = $shortcodesObject->process([$shortcode]);
    expect($result[0])->equals('success');
    $this->settings->set('tracking.enabled', true);
    // tracking function only works during sending, so queue object must not be false
    $shortcodesObject->setQueue($this->_createQueue());
    $result = $shortcodesObject->process([$shortcode], 'x');
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
    wp_create_user('phoenix_test_user', 'pass', 'phoenix@test.com');
    $wPUser = get_user_by('login', 'phoenix_test_user');
    return $wPUser;
  }

  public function _createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setFirstName('Donald');
    $subscriber->setLastName('Trump');
    $subscriber->setEmail('mister@trump.com');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setWpUserId($this->wPUser->ID);
    $subscriber->setLinkToken(Security::generateHash());
    return $subscriber;
  }

  public function _createNewsletter(NewsletterEntity $parent = null, $type = Newsletter::TYPE_NOTIFICATION): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('some subject');
    $newsletter->setType($type);
    $newsletter->setHash(Security::generateHash());
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    if ($parent) {
      $newsletter->setParent($parent);
    }
    return $newsletter;
  }

  /** @return SendingQueueEntity */
  public function _createQueue(): SendingQueueEntity {
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($this->newsletter);
    return $queue;
  }

  public function _after() {
    $this->cleanup();
  }

  public function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
    if ($this->wPPost) wp_delete_post($this->wPPost, true);
    if ($this->wPUser) wp_delete_user($this->wPUser->ID);
  }
}
