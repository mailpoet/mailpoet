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
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkNormalizer;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
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

  public function testItHooksToPostRenderToNormalizeTagLinksForTracking() {
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

    // Context-dependent tag links are stored symbolically and tracked
    $unsubscribeLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[mailpoet/subscription-unsubscribe-url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $unsubscribeLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $unsubscribeLink->getHash() . '">Unsubscribe</a>', $rendered['html']);

    $manageLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[mailpoet/subscription-manage-url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $manageLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $manageLink->getHash() . '">Manage</a>', $rendered['html']);

    $viewInBrowserLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[mailpoet/newsletter-view-in-browser-url]']);
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

  /**
   * The order review tag is registered only once the automation subjects are known, i.e. after
   * link tracking ran, so normalization must not depend on the registry.
   */
  public function testItNormalizesOrderReviewUrlHrefToTokenBeforeLinkTracking(): void {
    // The registry is cached across tests, so make sure the tag is not registered here
    // no matter what ran before; normalization must not depend on it.
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $registry->unregister('[woocommerce/order-review-url]');
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->normalizeTrackedLinks([
      'html' => '<a href="[woocommerce/order-review-url]">Leave a review</a>'
        . '<a href="http://%5Bwoocommerce/order-review-url%5D">Encoded</a>'
        . '<a data-link-href="[woocommerce/order-review-url]" contenteditable="false">Inserted as link</a>',
      'text' => '[Leave a review](http://%5Bwoocommerce/order-review-url%5D) [Other](https://example.com/?a=1)',
    ]);

    $this->assertSame(3, substr_count($emailContent['html'], 'href="[woocommerce/order-review-url]"'));
    $this->assertStringNotContainsString('%5D', $emailContent['html']);
    $this->assertStringNotContainsString('.invalid', $emailContent['html']);
    $this->assertStringNotContainsString('data-link-href', $emailContent['html']);
    $this->assertStringNotContainsString('contenteditable', $emailContent['html']);
    $this->assertSame('[Leave a review]([woocommerce/order-review-url]) [Other](https://example.com/?a=1)', $emailContent['text']);
  }

  public function testItNormalizesAnyTagShapedToken(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->normalizeTrackedLinks([
      'html' => '<a data-link-href="[acme/custom-url]">Custom</a><a href="http://%5Bmailpoet/subscriber-activation-link%5D">Activate</a>',
    ]);

    $this->assertStringContainsString('href="[acme/custom-url]"', $emailContent['html']);
    $this->assertStringContainsString('href="[mailpoet/subscriber-activation-link]"', $emailContent['html']);
    $this->assertStringNotContainsString('data-link-href', $emailContent['html']);
  }

  /**
   * @dataProvider textLinkTargetProvider
   */
  public function testItNormalizesTextLinkTargets(string $target, string $expected): void {
    $normalizer = new PersonalizationTagLinkNormalizer();
    $this->assertSame(
      '[Link](' . $expected . ')',
      $normalizer->normalizeText('[Link](' . $target . ')', ['[acme/static-url]' => 'https://example.com/static'])
    );
  }

  /**
   * @return array<string, array{string, string}>
   */
  public function textLinkTargetProvider(): array {
    return [
      'bare token' => ['[acme/order-url]', '[acme/order-url]'],
      'http prefix' => ['http://[acme/order-url]', '[acme/order-url]'],
      'https prefix' => ['https://[acme/order-url]', '[acme/order-url]'],
      'encoded brackets' => ['http://%5Bacme/order-url%5D', '[acme/order-url]'],
      'closing bracket encoded' => ['http://[acme/order-url%5D', '[acme/order-url]'],
      'html entities' => ['&#91;acme/order-url&#93;', '[acme/order-url]'],
      'pre-tracking token' => ['http://%5Bacme/static-url%5D', 'https://example.com/static'],
      'regular url' => ['https://example.com/?q=[a]', 'https://example.com/?q=[a]'],
      'legacy shortcode' => ['[link:subscription_unsubscribe_url]', '[link:subscription_unsubscribe_url]'],
    ];
  }

  public function testItLeavesNonTokenLinksUntouched(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $html = '<a href="[postLink]">Post</a><a href="https://example.com/?q=[a]">Regular</a><a href="[link:subscription_manage_url]">Legacy</a><p>http://[not-a-mailpoet-token]</p>';
    $emailContent = $personalizationManager->normalizeTrackedLinks(['html' => $html]);

    $this->assertSame($html, $emailContent['html']);
  }

  public function testItResolvesEncodedHomepageUrlHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->normalizeTrackedLinks([
      'html' => '<a href="http://%5Bmailpoet/site-homepage-url%5D">Homepage</a>',
      'text' => '[Homepage](http://%5Bmailpoet/site-homepage-url%5D)',
    ]);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    $this->assertStringNotContainsString('%5Bmailpoet/site-homepage-url%5D', $emailContent['html']);
    $this->assertStringNotContainsString('[mailpoet/site-homepage-url]', $emailContent['html']);
    $this->assertSame('[Homepage](' . $homepageUrl . ')', $emailContent['text']);
  }

  public function testItResolvesUnencodedHomepageUrlHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->normalizeTrackedLinks([
      'html' => '<a href="[mailpoet/site-homepage-url]">Homepage</a>',
    ]);

    $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
    $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    $this->assertStringNotContainsString('[mailpoet/site-homepage-url]', $emailContent['html']);
  }

  public function testItResolvesHomepageUrlDataLinkHrefBeforeLinkTracking(): void {
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

    $emailContent = $personalizationManager->normalizeTrackedLinks([
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
    $registry->register($this->createTag('woocommerce/store-url', 'https://example.com/shop'));

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->normalizeTrackedLinks([
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

  public function testItTracksPreTrackingTokensSymbolicallyWhenTheirCallbackFails(): void {
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

      $emailContent = $personalizationManager->normalizeTrackedLinks([
        'html' => '<a href="http://%5Bwoocommerce/store-url%5D">Shop now</a><a href="[mailpoet/site-homepage-url]">Homepage</a>',
      ]);

      // The failing tag falls back to click-time resolution, other tokens still resolve
      $this->assertStringContainsString('href="[woocommerce/store-url]"', $emailContent['html']);
      $homepageUrl = (string)WPFunctions::get()->getBloginfo('url');
      $this->assertStringContainsString('href="' . $homepageUrl . '"', $emailContent['html']);
    } finally {
      $registry->unregister('[woocommerce/store-url]');
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItTracksPreTrackingTokensSymbolicallyWhenTheirCallbackReturnsNoUrl(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $originalTag = $registry->unregister('[woocommerce/store-url]');
    $registry->register(new Personalization_Tag(
      'Store URL',
      'woocommerce/store-url',
      'Store',
      function (): string {
        return '';
      }
    ));

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->normalizeTrackedLinks([
        'html' => '<a href="http://%5Bwoocommerce/store-url%5D">Shop now</a>',
      ]);

      $this->assertStringContainsString('href="[woocommerce/store-url]"', $emailContent['html']);
    } finally {
      $registry->unregister('[woocommerce/store-url]');
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItTracksUnregisteredPreTrackingUrlTokensSymbolically(): void {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $originalTag = $registry->unregister('[woocommerce/my-account-url]');

    try {
      $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);

      $emailContent = $personalizationManager->normalizeTrackedLinks([
        'html' => '<a href="http://%5Bwoocommerce/my-account-url%5D">My account</a>',
      ]);

      $this->assertStringContainsString('href="[woocommerce/my-account-url]"', $emailContent['html']);
    } finally {
      if ($originalTag) {
        $registry->register($originalTag);
      }
    }
  }

  public function testItNormalizesTrackingOptOutTagLink(): void {
    $normalizer = new PersonalizationTagLinkNormalizer();
    $html = '<a data-link-href="[mailpoet/subscription-tracking-opt-out-url]" href="#">Stop tracking me</a>';
    $result = $normalizer->normalizeHtml($html, []);
    $this->assertStringContainsString('href="[mailpoet/subscription-tracking-opt-out-url]"', $result);
    $this->assertStringNotContainsString('href="#"', $result);
    $this->assertStringNotContainsString('data-link-href', $result);
  }

  private function createTag(string $token, string $value): Personalization_Tag {
    return new Personalization_Tag(
      $token,
      $token,
      'Test',
      function () use ($value): string {
        return $value;
      }
    );
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
}
