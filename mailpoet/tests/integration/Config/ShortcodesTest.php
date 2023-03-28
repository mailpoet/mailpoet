<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Helper\WordPress;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

//phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

class ShortcodesTest extends \MailPoetTest {
  /** @var SendingQueueEntity */
  public $queue;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var Url */
  private $newsletterUrl;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscriberFactory = new SubscriberFactory();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->newsletterUrl = $this->diContainer->get(Url::class);
    $newsletterFactory = new NewsletterFactory();

    $this->newsletter = $newsletterFactory
      ->withSubject('')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $this->queue = $this->newsletter->getLatestQueue();
  }

  public function testItGetsArchives() {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    WordPress::interceptFunction('apply_filters', function() use($shortcodes) {
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    // result contains a link pointing to the "view in browser" router endpoint
    $result = $shortcodes->getArchive($params = false);
    WordPress::releaseFunction('apply_filters');
    $dom = pQuery::parseStr($result);
    $link = $dom->query('a');
    /** @var string $link */
    $link = $link->attr('href');
    expect($link)->stringContainsString('endpoint=view_in_browser');
    $parsedLink = parse_url($link, PHP_URL_QUERY);
    parse_str(html_entity_decode((string)$parsedLink), $data);
    $requestData = $this->newsletterUrl->transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
    expect($requestData['newsletter_hash'])->equals($this->newsletter->getHash());
  }

  public function testItRendersShortcodeDefaultsInSubject() {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $this->queue->setNewsletterRenderedSubject('Hello [subscriber:firstname | default:reader]');
    $this->entityManager->persist($this->queue);
    $this->entityManager->flush();

    WordPress::interceptFunction('apply_filters', function() use($shortcodes) {
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    $result = $shortcodes->getArchive($params = false);
    WordPress::releaseFunction('apply_filters');
    expect((string)$result)->stringContainsString('Hello reader');
  }

  public function testItRendersSubscriberDetailsInSubject() {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $userData = ["ID" => 1, "first_name" => "Foo", "last_name" => "Bar"];
    $currentUser = new \WP_User((object)$userData, "FooBar");
    $wpUser = wp_set_current_user($currentUser->ID);
    expect((new WPFunctions)->isUserLoggedIn())->true();

    $this->subscriberFactory
      ->withFirstName('Foo')
      ->withLastName('Bar')
      ->withEmail($wpUser->user_email)
      ->withWpUserId($currentUser->ID)
      ->create();

    $this->queue->setNewsletterRenderedSubject('Hello [subscriber:firstname | default:d_firstname] [subscriber:lastname | default:d_lastname]');
    $this->entityManager->persist($this->queue);
    $this->entityManager->flush();

    WordPress::interceptFunction('apply_filters', function() use($shortcodes) {
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    $result = $shortcodes->getArchive($params = false);
    WordPress::releaseFunction('apply_filters');
    expect((string)$result)->stringContainsString("Hello {$currentUser->first_name} {$currentUser->last_name}");
  }

  public function testItDisplaysManageSubscriptionFormForLoggedinExistingUsers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();

    $subscriber = $this->subscriberFactory
      ->withEmail($wpUser->user_email)
      ->withWpUserId($wpUser->ID)
      ->create();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    expect($result)->stringContainsString('form class="mailpoet-manage-subscription" method="post"');
    expect($result)->stringContainsString($subscriber->getEmail());
  }

  public function testItAppliesFilterForManageSubscriptionForm() {
    $wpUser = wp_set_current_user(1);
    $wp = new WPFunctions;
    expect($wp->isUserLoggedIn())->true();

    $this->subscriberFactory
      ->withEmail($wpUser->user_email)
      ->withWpUserId($wpUser->ID)
      ->create();

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

    expect($this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]))->null(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

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

    $this->subscriberFactory
      ->withEmail($wpUser->user_email)
      ->withWpUserId($wpUser->ID)
      ->create();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    expect($result)->stringContainsString('Manage your subscription');
  }

  public function testItDoesNotDisplayLinkToManageSubscriptionPageForLoggedinNonexistentSubscribers() {
    $wpUser = wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    expect($this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]))->null(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

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
}
