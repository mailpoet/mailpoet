<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Helper\WordPress;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Url;
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
      /** @var array{0: string, 1:NewsletterEntity, 2:SubscriberEntity|null, 3:SendingQueueEntity|null} $args */
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_email_processed_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_email_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    // result contains a link pointing to the public share URL
    $result = $shortcodes->getArchive();
    WordPress::releaseFunction('apply_filters');
    $dom = pQuery::parseStr($result);
    $link = $dom->query('a');
    /** @var string $link */
    $link = $link->attr('href');
    verify($link)->equals($this->newsletterUrl->getPublicShareUrl($this->newsletter));
    verify($link)->stringNotContainsString('endpoint=view_in_browser');
  }

  public function testArchiveListsPublicAndPrivateEmails(): void {
    (new NewsletterFactory())
      ->withSubject('Public newsletter')
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    (new NewsletterFactory())
      ->withSubject('Private newsletter')
      ->withSentStatus()
      ->withSendingQueue()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();

    $result = do_shortcode('[mailpoet_archive]');

    verify($result)->stringContainsString('Public newsletter');
    verify($result)->stringContainsString('Private newsletter');
  }

  public function testRenderArchiveSubjectLinksNotificationHistoryToViewInBrowser(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Post notification subject')
      ->withPostNotificationHistoryType()
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $result = ContainerWrapper::getInstance()
      ->get(Shortcodes::class)
      ->renderArchiveSubject($newsletter, null, $queue);

    verify($result)->stringContainsString('Post notification subject');
    verify($result)->stringContainsString('<a ');
    verify($result)->stringContainsString('endpoint=view_in_browser');
    verify($result)->stringNotContainsString($this->newsletterUrl->getPublicShareUrl($newsletter));
  }

  public function testRenderArchiveSubjectLinksPrivateEmailsToViewInBrowser(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Private subject')
      ->withSentStatus()
      ->withSendingQueue()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $result = ContainerWrapper::getInstance()
      ->get(Shortcodes::class)
      ->renderArchiveSubject($newsletter, null, $queue);

    verify($result)->stringContainsString('Private subject');
    verify($result)->stringContainsString('<a ');
    verify($result)->stringContainsString('endpoint=view_in_browser');
    verify($result)->stringNotContainsString($this->newsletterUrl->getPublicShareUrl($newsletter));
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
    verify($result)->stringNotContainsString('Newsletter 1');
    verify($result)->stringContainsString('Newsletter 2');
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
    verify($result)->stringContainsString('Newsletter 1');
    verify($result)->stringNotContainsString('Newsletter 2');
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
    verify($result)->stringNotContainsString('Newsletter 1');
    verify($result)->stringContainsString('Newsletter 2');
    verify($result)->stringNotContainsString('Newsletter 3');
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
    verify($result)->stringContainsString('Great subject');
    verify($result)->stringContainsString('Subject that is great');
    verify($result)->stringNotContainsString('Good subject');
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
    verify($result)->stringContainsString('Newsletter 1');
    verify($result)->stringNotContainsString('Newsletter 2');
  }

  public function testArchiveIgnoresNegativeLastNDays(): void {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $parsed = $shortcodes->getParsedArchiveParams(['in_the_last_days' => '-5']);
    verify($parsed['startDate'])->null();
    verify($parsed['endDate'])->null();
  }

  public function testArchiveIgnoresNonScalarLastNDays(): void {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $parsed = $shortcodes->getParsedArchiveParams(['in_the_last_days' => ['7']]);
    verify($parsed['startDate'])->null();
    verify($parsed['endDate'])->null();
  }

  public function testArchiveDefaultsLimitWhenLimitIsMissingOrInvalid(): void {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);

    $limits = [
      [],
      ['limit' => ''],
      ['limit' => '0'],
      ['limit' => '-5'],
      ['limit' => ['5']],
    ];

    foreach ($limits as $params) {
      $parsed = $shortcodes->getParsedArchiveParams($params);
      verify($parsed['limit'])->equals(Shortcodes::DEFAULT_ARCHIVE_LIMIT);
    }
  }

  public function testArchiveAcceptsExplicitLimit(): void {
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);

    $parsed = $shortcodes->getParsedArchiveParams(['limit' => '7']);
    verify($parsed['limit'])->equals(7);
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
    verify($result)->stringNotContainsString('Newsletter 1');
    verify($result)->stringContainsString('Newsletter 2');
  }

  public function testArchiveUsesDefaultLimitWhenLimitIsMissing(): void {
    for ($i = 0; $i <= Shortcodes::DEFAULT_ARCHIVE_LIMIT; $i++) {
      (new NewsletterFactory())
        ->withSendingQueue(['processed_at' => Carbon::now()->subDays($i)])
        ->withSentStatus()
        ->withSubject(sprintf('Archive item %03d', $i))
        ->create();
    }

    $result = do_shortcode('[mailpoet_archive]');
    verify($result)->stringContainsString('Archive item 000');
    verify($result)->stringContainsString(sprintf('Archive item %03d', Shortcodes::DEFAULT_ARCHIVE_LIMIT - 1));
    verify($result)->stringNotContainsString(sprintf('Archive item %03d', Shortcodes::DEFAULT_ARCHIVE_LIMIT));
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
    verify($result)->stringContainsString('Newsletter 1');
    verify($result)->stringContainsString('Newsletter 2');
    verify($result)->stringContainsString('Newsletter 3');

    $result = do_shortcode('[mailpoet_archive limit="2"]');
    verify($result)->stringContainsString('Newsletter 1');
    verify($result)->stringContainsString('Newsletter 2');
    verify($result)->stringNotContainsString('Newsletter 3');

    $result = do_shortcode('[mailpoet_archive limit="1"]');
    verify($result)->stringContainsString('Newsletter 1');
    verify($result)->stringNotContainsString('Newsletter 2');
    verify($result)->stringNotContainsString('Newsletter 3');
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
      /** @var array{0: string, 1:NewsletterEntity, 2:SubscriberEntity|null, 3:SendingQueueEntity|null} $args */
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_email_processed_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_email_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    $result = $shortcodes->getArchive();
    WordPress::releaseFunction('apply_filters');
    verify((string)$result)->stringContainsString('Hello reader');
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
    verify((new WPFunctions)->isUserLoggedIn())->true();

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
      /** @var array{0: string, 1:NewsletterEntity, 2:SubscriberEntity|null, 3:SendingQueueEntity|null} $args */
      $args = func_get_args();
      $filterName = array_shift($args);
      switch ($filterName) {
        case 'mailpoet_archive_email_processed_date':
          return $shortcodes->renderArchiveDate($args[0]);
        case 'mailpoet_archive_email_subject_line':
          return $shortcodes->renderArchiveSubject($args[0], $args[1], $args[2]);
      }
      return '';
    });
    $result = $shortcodes->getArchive();
    WordPress::releaseFunction('apply_filters');
    verify((string)$result)->stringContainsString("Hello {$currentUser->first_name} {$currentUser->last_name}");
  }

  public function testItDisplaysManageSubscriptionFormForLoggedinExistingUsers() {
    $wpUser = wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();

    $subscriber = $this->subscriberFactory
      ->withEmail($wpUser->user_email)
      ->withWpUserId($wpUser->ID)
      ->create();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    verify($result)->stringContainsString('form class="mailpoet-manage-subscription');
    verify($result)->stringContainsString($subscriber->getEmail());
  }

  public function testItAppliesFilterForManageSubscriptionForm() {
    $wpUser = wp_set_current_user(1);
    $wp = new WPFunctions;
    verify($wp->isUserLoggedIn())->true();

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
    verify($result)->stringContainsString('form class="mailpoet-manage-subscription');
    verify($result)->stringContainsString('MY CUSTOM CONTENT');
    $wp->removeAllActions('mailpoet_manage_subscription_page');
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedinNonexistentSubscribers() {
    $wpUser = wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();

    verify($this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]))->null(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    verify($result)->stringContainsString('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionFormForLoggedOutUsers() {
    wp_set_current_user(0);
    verify((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage_subscription]');
    verify($result)->stringContainsString('Subscription management form is only available to mailing lists subscribers.');
  }

  public function testItDisplaysLinkToManageSubscriptionPageForLoggedinExistingUsers() {
    $wpUser = wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();

    $this->subscriberFactory
      ->withEmail($wpUser->user_email)
      ->withWpUserId($wpUser->ID)
      ->create();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    verify($result)->stringContainsString('Manage your subscription');
  }

  public function testItDoesNotDisplayLinkToManageSubscriptionPageForLoggedinNonexistentSubscribers() {
    $wpUser = wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();
    verify($this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]))->null(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    verify($result)->stringContainsString('Link to subscription management page is only available to mailing lists subscribers.');
  }

  public function testItDoesNotDisplayManageSubscriptionPageForLoggedOutUsers() {
    wp_set_current_user(0);
    verify((new WPFunctions)->isUserLoggedIn())->false();

    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes->init();
    $result = do_shortcode('[mailpoet_manage]');
    verify($result)->stringContainsString('Link to subscription management page is only available to mailing lists subscribers.');
  }
}
