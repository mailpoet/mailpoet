<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Codeception\Util\Fixtures;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\BlockEmailPersonalizationProcessor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\LinksToShortcodesConvertor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\Placeholders\PlaceholderCollector;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\WP\Functions as WPFunctions;

class PersonalizationTagManagerTest extends \MailPoetTest {
  private NewsletterTask $newsletterTask;
  private NewsletterEntity $newsletter;
  private SendingQueueEntity $sendingQueueEntity;
  private ScheduledTaskEntity $scheduledTaskEntity;

  public function _before() {
    parent::_before();
    $this->newsletterTask = new NewsletterTask();
    $this->newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->create();

    $this->scheduledTaskEntity = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->sendingQueueEntity = (new SendingQueueFactory())->create($this->scheduledTaskEntity, $this->newsletter);
  }

  public function testItHooksToPostRenderToReplaceLinksInHrefByShortcodes() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    // @phpstan-ignore-next-line The structure is hardcoded in the fixture
    $body['content']['blocks'][0]['blocks'][0]['blocks'][0]['text'] = '
        <a data-link-href="[mailpoet/subscription-unsubscribe-url]">Unsubscribe</a>
        <a data-link-href="[mailpoet/subscription-manage-url]">Manage</a>
        <a data-link-href="[mailpoet/newsletter-view-in-browser-url]">View in browser</a>
        <a href="http://%5Bmailpoet/site-homepage-url%5D">Homepage</a>
        <!--[mailpoet/subscription-unsubscribe-url]-->
      ';
    $this->newsletter->setBody((array)$body);
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);
    $personalizationManager->initialize();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);

    $newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);

    /** @var array{html: string, text: string}  $rendered */
    $rendered = $this->sendingQueueEntity->getNewsletterRenderedBody();

    // Ensure link were properly extracted and replaced in email body
    $unsubscribeLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:subscription_unsubscribe_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $unsubscribeLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $unsubscribeLink->getHash() . '">Unsubscribe</a>', $rendered['html']);

    $manageLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:subscription_manage_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $manageLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $manageLink->getHash() . '">Manage</a>', $rendered['html']);

    $viewInBrowserLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:newsletter_view_in_browser_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $viewInBrowserLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $viewInBrowserLink->getHash() . '">View in browser</a>', $rendered['html']);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $homepageLink = $this->findNewsletterLinkStartingWithUrl($homepageUrl);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $homepageLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $homepageLink->getHash() . '">Homepage</a>', $rendered['html']);
    $this->assertNull($newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => 'http://%5Bmailpoet/site-homepage-url%5D']));

    // Tag placed out of href was not replaced
    $this->assertStringContainsString('<!--[mailpoet/subscription-unsubscribe-url]-->', $rendered['html']);
  }

  public function testItRegistersPersonalizationTagsForLegacyShortcodes(): void {
    $customField = (new CustomFieldFactory())->create();

    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);
    $personalizationManager->initialize();

    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    WPFunctions::get()->applyFilters('woocommerce_email_editor_register_personalization_tags', $registry);

    $expectedTokens = [
      '[mailpoet/subscriber-displayname]',
      '[mailpoet/subscriber-count]',
      '[mailpoet/subscriber-cf-' . $customField->getId() . ']',
      '[mailpoet/newsletter-subject]',
      '[mailpoet/date-day]',
      '[mailpoet/date-day-ordinal]',
      '[mailpoet/date-day-name]',
      '[mailpoet/date-month]',
      '[mailpoet/date-month-name]',
      '[mailpoet/date-year]',
    ];

    foreach ($expectedTokens as $token) {
      $this->assertNotNull($registry->get_by_token($token), "Expected personalization tag {$token} to be registered.");
    }
  }

  public function testItRegistersOrderReviewUrlTagForOrderAutomations(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $registry->unregister('[woocommerce/order-review-url]');

    $orderReviewUrl = $this->createMock(OrderReviewUrl::class);
    $orderReviewUrl->method('isSupported')->willReturn(true);
    $personalizationManager = $this->getServiceWithOverrides(PersonalizationTagManager::class, [
      'orderReviewUrl' => $orderReviewUrl,
    ]);
    $personalizationManager->extendWooCommerceTagsForMailPoet($registry, [OrderSubject::KEY]);

    $tag = $registry->get_by_token('[woocommerce/order-review-url]');
    $this->assertNotNull($tag);
    $this->assertSame('[woocommerce/order-review-url]', $tag->get_token());
    $this->assertContains(EmailEditor::MAILPOET_EMAIL_POST_TYPE, $tag->get_post_types());
  }

  public function testItDoesNotRegisterOrderReviewUrlTagWithoutOrderSubject(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $registry->unregister('[woocommerce/order-review-url]');

    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);
    $personalizationManager->extendWooCommerceTagsForMailPoet($registry, ['mailpoet:subscriber']);

    $this->assertNull($registry->get_by_token('[woocommerce/order-review-url]'));
  }

  public function testItMovesOrderReviewUrlHrefToDataAttributeBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a href="[woocommerce/order-review-url]">Leave a review</a>',
    ]);

    $this->assertStringContainsString('data-link-href="[woocommerce/order-review-url]"', $emailContent['html']);
    $this->assertStringNotContainsString(' href="[woocommerce/order-review-url]"', $emailContent['html']);
  }

  public function testItMovesEncodedOrderReviewUrlHrefToDataAttributeBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a href="http://%5Bwoocommerce/order-review-url%5D">Leave a review</a>',
    ]);

    $this->assertStringContainsString('data-link-href="[woocommerce/order-review-url]"', $emailContent['html']);
    $this->assertStringNotContainsString('%5Bwoocommerce/order-review-url%5D', $emailContent['html']);
  }

  public function testItResolvesEncodedHomepageUrlHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a href="http://%5Bmailpoet/site-homepage-url%5D">Homepage</a>',
    ]);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    $this->assertStringNotContainsString('%5Bmailpoet/site-homepage-url%5D', $emailContent['html']);
    $this->assertStringNotContainsString('[mailpoet/site-homepage-url]', $emailContent['html']);
  }

  public function testItResolvesUnencodedHomepageUrlHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a href="[mailpoet/site-homepage-url]">Homepage</a>',
    ]);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    $this->assertStringNotContainsString('[mailpoet/site-homepage-url]', $emailContent['html']);
  }

  public function testItResolvesHomepageUrlDataLinkHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a data-link-href="[mailpoet/site-homepage-url]" contenteditable="false">Homepage</a>',
    ]);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    $this->assertStringNotContainsString('data-link-href=', $emailContent['html']);
    $this->assertStringNotContainsString('contenteditable=', $emailContent['html']);
  }

  public function testItResolvesRegisteredWooCommerceUrlTokensBeforeLinkTracking(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $originalTag = $registry->unregister('[woocommerce/store-url]');
    $registry->register(new Personalization_Tag(
      'Store URL',
      'woocommerce/store-url',
      'Store',
      function (): string {
        return 'https://example.com/shop';
      }
    ));

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->convertLinksToShortcodes([
        'html' => '<a href="http://%5Bwoocommerce/store-url%5D">Shop now</a>',
      ]);

      $this->assertStringContainsString('href="https://example.com/shop"', $emailContent['html']);
      $this->assertStringNotContainsString('%5Bwoocommerce/store-url%5D', $emailContent['html']);
    } finally {
      $registry->unregister('[woocommerce/store-url]');
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItSkipsPreTrackingTokensWhoseCallbackFails(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $originalTag = $registry->unregister('[woocommerce/store-url]');
    $registry->register(new Personalization_Tag(
      'Store URL',
      'woocommerce/store-url',
      'Store',
      function (): string {
        throw new \RuntimeException('Broken tag callback');
      }
    ));

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->convertLinksToShortcodes([
        'html' => '<a href="http://%5Bwoocommerce/store-url%5D">Shop now</a><a href="[mailpoet/site-homepage-url]">Homepage</a>',
      ]);

      // The failing tag is skipped, other tokens still resolve
      $this->assertStringContainsString('href="http://%5Bwoocommerce/store-url%5D"', $emailContent['html']);
      $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
      $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    } finally {
      $registry->unregister('[woocommerce/store-url]');
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItLeavesUnregisteredPreTrackingUrlTokensUntouched(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $originalTag = $registry->unregister('[woocommerce/my-account-url]');

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->convertLinksToShortcodes([
        'html' => '<a href="http://%5Bwoocommerce/my-account-url%5D">My account</a>',
      ]);

      $this->assertStringContainsString('href="http://%5Bwoocommerce/my-account-url%5D"', $emailContent['html']);
    } finally {
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItOnlyRemovesTemporaryHttpPrefixForKnownLinkTokens(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->convertLinksToShortcodes([
      'html' => '<a data-link-href="[mailpoet/subscription-unsubscribe-url]">Unsubscribe</a><p>http://[not-a-mailpoet-token]</p>',
    ]);

    $this->assertStringContainsString('href="[link:subscription_unsubscribe_url]"', $emailContent['html']);
    $this->assertStringContainsString('http://[not-a-mailpoet-token]', $emailContent['html']);
  }

  public function testItRestoresPersonalizedLinkHrefsAfterPersonalization(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $html = $personalizationManager->restorePersonalizedLinkHrefs(
      '<a data-link-href="https://example.com/review-order/abc">Leave a review</a>'
    );

    $this->assertStringContainsString('href="https://example.com/review-order/abc"', $html);
    $this->assertStringNotContainsString('data-link-href=', $html);
  }

  public function testItConvertsTrackingOptOutTagToShortcode(): void {
    $convertor = new LinksToShortcodesConvertor();
    $html = '<a data-link-href="[mailpoet/subscription-tracking-opt-out-url]" href="#">Stop tracking me</a>';
    $result = $convertor->convertLinkTagsToShortcodes($html);
    $this->assertStringContainsString('[link:subscription_tracking_opt_out_url]', $result);
  }

  public function testItResolvesOrderReviewUrlDataAttributeAfterPersonalization(): void {
    $convertor = new LinksToShortcodesConvertor();

    $html = $convertor->restorePersonalizedLinkHrefs(
      '<a data-link-href="[woocommerce/order-review-url]">Leave a review</a>',
      ['[woocommerce/order-review-url]' => 'https://example.com/review-order/abc']
    );

    $this->assertStringContainsString('href="https://example.com/review-order/abc"', $html);
    $this->assertStringNotContainsString('data-link-href=', $html);
  }

  public function testItResolvesOrderReviewUrlInPlainTextAfterPersonalization(): void {
    $convertor = new LinksToShortcodesConvertor();

    $text = $convertor->restorePersonalizedLinkUrls(
      '[Leave a review](http://[woocommerce/order-review-url%5D)',
      ['[woocommerce/order-review-url]' => 'https://example.com/review-order/abc']
    );

    $this->assertSame('[Leave a review](https://example.com/review-order/abc)', $text);
  }

  public function testItSkipsCustomFieldTagsWhenDeletedAtColumnIsMissing(): void {
    $customField = (new CustomFieldFactory())->create();

    $table = $this->entityManager->getClassMetadata(CustomFieldEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $dropped = false;

    try {
      // Reproduce the plugin-update window: the deleted_at column the entity maps has not been added yet.
      $connection->executeStatement("ALTER TABLE `{$table}` DROP COLUMN `deleted_at`");
      $dropped = true;

      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);
      $personalizationManager->initialize();

      $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
      // Must not throw an uncaught QueryException / fatal.
      WPFunctions::get()->applyFilters('woocommerce_email_editor_register_personalization_tags', $registry);

      // The custom-field tag is skipped because its query hit the missing column...
      $this->assertNull($registry->get_by_token('[mailpoet/subscriber-cf-' . $customField->getId() . ']'));
      // ...but the static (non-DB) tags still register.
      $this->assertNotNull($registry->get_by_token('[mailpoet/subscriber-email]'));
    } finally {
      if ($dropped) {
        $connection->executeStatement("ALTER TABLE `{$table}` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL");
      }
    }
  }

  private function findNewsletterLinkStartingWithUrl(string $url): ?NewsletterLinkEntity {
    $newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
    /** @var NewsletterLinkEntity[] $newsletterLinks */
    $newsletterLinks = $newsletterLinkRepository->findBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity]);
    foreach ($newsletterLinks as $newsletterLink) {
      if (strpos($newsletterLink->getUrl(), $url) === 0) {
        return $newsletterLink;
      }
    }
    return null;
  }

  public function testBlockEmailPersonalizationProcessorRestoresOrderReviewUrlWithoutPostPersonalizationFilters(): void {
    $orderReviewUrl = $this->createMock(OrderReviewUrl::class);
    $orderReviewUrl->method('getUrl')->willReturn('https://example.com/review-order/abc');
    $processor = $this->getServiceWithOverrides(BlockEmailPersonalizationProcessor::class, [
      'orderReviewUrl' => $orderReviewUrl,
    ]);

    $content = $processor->personalize([
      'Subject',
      '<a data-link-href="[woocommerce/order-review-url]">Leave a review</a>',
      '[Leave a review](http://[woocommerce/order-review-url%5D)',
    ], []);

    $this->assertSame('Subject', $content[0]);
    $this->assertStringContainsString('href="https://example.com/review-order/abc"', $content[1]);
    $this->assertStringNotContainsString('data-link-href=', $content[1]);
    $this->assertSame('[Leave a review](https://example.com/review-order/abc)', $content[2]);
  }

  public function testBlockEmailPersonalizationProcessorCanReturnPlaceholdersAndValues(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $registry->register(new Personalization_Tag(
      'Test Name',
      'mailpoet/test-name',
      'Test',
      function(): string {
        return 'Rosta & Co';
      }
    ));
    $registry->register(new Personalization_Tag(
      'Test URL',
      'mailpoet/test-url',
      'Test',
      function(): string {
        return 'https://example.com/review-order/abc?email=rosta%40example.com&source=mss';
      }
    ));

    $collector = new PlaceholderCollector('test');
    $orderReviewUrl = $this->createMock(OrderReviewUrl::class);
    $orderReviewUrl->method('getUrl')->willReturn('https://example.com/review-order/abc?email=rosta%40example.com&source=mss');
    $processor = $this->getServiceWithOverrides(BlockEmailPersonalizationProcessor::class, [
      'orderReviewUrl' => $orderReviewUrl,
    ]);
    $source = [
      'Subject',
      '<p><!--[mailpoet/test-name]--></p><a data-link-href="[mailpoet/test-url]">Review</a>',
      '<!--[mailpoet/test-name]--> [Review](http://[woocommerce/order-review-url%5D)',
    ];
    $personalizedContent = $processor->personalize($source, []);
    $content = $processor->personalizeWithPlaceholders($source, [], $collector);

    $this->assertSame('Subject', $content[0]);
    $this->assertStringContainsString('<p>{{mailpoet_mss_test_1}}</p>', $content[1]);
    $this->assertStringContainsString('href="{{mailpoet_mss_test_2}}"', $content[1]);
    $this->assertSame('{{mailpoet_mss_test_3}} [Review]({{mailpoet_mss_test_4}})', $content[2]);
    $this->assertSame($personalizedContent[0], strtr($content[0], $collector->getValues()));
    $this->assertSame($personalizedContent[1], strtr($content[1], $collector->getValues()));
    $this->assertSame($personalizedContent[2], strtr($content[2], $collector->getValues()));
    $this->assertSame([
      '{{mailpoet_mss_test_1}}' => 'Rosta & Co',
      '{{mailpoet_mss_test_2}}' => 'https://example.com/review-order/abc?email=rosta%40example.com&#038;source=mss',
      '{{mailpoet_mss_test_3}}' => 'Rosta & Co',
      '{{mailpoet_mss_test_4}}' => 'https://example.com/review-order/abc?email=rosta%40example.com&source=mss',
    ], $collector->getValues());
  }
}
