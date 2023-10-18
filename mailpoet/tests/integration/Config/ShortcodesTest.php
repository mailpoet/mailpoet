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
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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
  }

  public function testItGetsArchives() {
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory
      ->withSubject('')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $this->queue = $this->newsletter->getLatestQueue();
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
    $result = $shortcodes->getArchive();
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
    verify($requestData['newsletter_hash'])->equals($this->newsletter->getHash());
  }

  public function testArchiveAcceptsStartDate() {
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-09-02')])
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-09-05')])
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();

    $result = do_shortcode('[mailpoet_archive start_date="2023-09-04"]');
    expect($result)->stringNotContainsString('Newsletter 1');
    expect($result)->stringContainsString('Newsletter 2');
  }

  public function testArchiveAcceptsEndDate(): void {
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-09-02')])
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-09-05')])
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();

    $result = do_shortcode('[mailpoet_archive end_date="2023-09-04"]');
    expect($result)->stringContainsString('Newsletter 1');
    expect($result)->stringNotContainsString('Newsletter 2');
  }

  public function testArchiveAcceptsStartAndEndDate(): void {
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-08-01')])
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-08-10')])
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => new Carbon('2023-08-15')])
      ->withSentStatus()
      ->withSubject('Newsletter 3')
      ->create();

    $result = do_shortcode('[mailpoet_archive start_date="2023-08-02" end_date="2023-08-14"]');
    expect($result)->stringNotContainsString('Newsletter 1');
    expect($result)->stringContainsString('Newsletter 2');
    expect($result)->stringNotContainsString('Newsletter 3');
  }

  public function testArchiveAcceptsSubjectSearch(): void {
    (new NewsletterFactory())
      ->withSendingQueue()
      ->withSentStatus()
      ->withSubject('Great subject')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue()
      ->withSentStatus()
      ->withSubject('Subject that is great')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue()
      ->withSentStatus()
      ->withSubject('Good subject')
      ->create();

    $result = do_shortcode('[mailpoet_archive subject_contains="great"]');
    expect($result)->stringContainsString('Great subject');
    expect($result)->stringContainsString('Subject that is great');
    expect($result)->stringNotContainsString('Good subject');
  }

  public function testArchiveAcceptsLastNDays(): void {
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => Carbon::now()->subDays(4)])
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => Carbon::now()->subDays(5)])
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();
    $result = do_shortcode('[mailpoet_archive in_the_last_days="4"]');
    expect($result)->stringContainsString('Newsletter 1');
    expect($result)->stringNotContainsString('Newsletter 2');
  }

  public function testArchiveAcceptsSegments(): void {
    $segment1 = (new Segment())->create();
    $segment2 = (new Segment())->create();
    (new NewsletterFactory())
      ->withSegments([$segment1])
      ->withSendingQueue()
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSegments([$segment2])
      ->withSendingQueue()
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();

    $result = do_shortcode(sprintf("[mailpoet_archive segments=\"%s\"]", $segment2->getId()));
    expect($result)->stringNotContainsString('Newsletter 1');
    expect($result)->stringContainsString('Newsletter 2');
  }

  public function testArchiveSupportsLimit() {
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => Carbon::now()->subDays(4)])
      ->withSentStatus()
      ->withSubject('Newsletter 1')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => Carbon::now()->subDays(5)])
      ->withSentStatus()
      ->withSubject('Newsletter 2')
      ->create();
    (new NewsletterFactory())
      ->withSendingQueue(['processed_at' => Carbon::now()->subDays(7)])
      ->withSentStatus()
      ->withSubject('Newsletter 3')
      ->create();

    $result = do_shortcode('[mailpoet_archive limit="3"]');
    expect($result)->stringContainsString('Newsletter 1');
    expect($result)->stringContainsString('Newsletter 2');
    expect($result)->stringContainsString('Newsletter 3');

    $result = do_shortcode('[mailpoet_archive limit="2"]');
    expect($result)->stringContainsString('Newsletter 1');
    expect($result)->stringContainsString('Newsletter 2');
    expect($result)->stringNotContainsString('Newsletter 3');

    $result = do_shortcode('[mailpoet_archive limit="1"]');
    expect($result)->stringContainsString('Newsletter 1');
    expect($result)->stringNotContainsString('Newsletter 2');
    expect($result)->stringNotContainsString('Newsletter 3');
  }

  public function testItRendersShortcodeDefaultsInSubject() {
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory
      ->withSubject('')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $this->queue = $this->newsletter->getLatestQueue();
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
    $result = $shortcodes->getArchive();
    WordPress::releaseFunction('apply_filters');
    expect((string)$result)->stringContainsString('Hello reader');
  }

  public function testItRendersSubscriberDetailsInSubject() {
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory
      ->withSubject('')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $this->queue = $this->newsletter->getLatestQueue();
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
    $result = $shortcodes->getArchive();
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
