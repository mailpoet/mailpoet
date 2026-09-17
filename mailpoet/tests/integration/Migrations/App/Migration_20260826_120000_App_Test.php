<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260826_120000_App_Test extends \MailPoetTest {
  /** @var Migration_20260826_120000_App */
  private $migration;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var array */
  private $originalWcOptions = [];

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260826_120000_App($this->diContainer);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wp = new WPFunctions();

    $wcOptionsToStub = [
      'woocommerce_store_address' => '123 Main St',
      'woocommerce_store_city' => 'New York',
      'woocommerce_store_postcode' => '10001',
      'woocommerce_default_country' => 'US:NY',
      'woocommerce_email_from_address' => 'store@example.com',
    ];
    foreach ($wcOptionsToStub as $option => $value) {
      $this->originalWcOptions[$option] = $this->wp->getOption($option);
      $this->wp->updateOption($option, $value);
    }
  }

  public function _after() {
    foreach ($this->originalWcOptions as $option => $value) {
      $this->wp->updateOption($option, $value);
    }
    parent::_after();
  }

  private function createTemplateAndPointSettingAtIt(array $body): NewsletterEntity {
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL)
      ->withBody($body)
      ->create();
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, $newsletter->getId());
    return $newsletter;
  }

  private function refetch(?int $newsletterId): NewsletterEntity {
    // findOneById() would otherwise return the identity-map instance and pass even if
    // run() never flushed anything; clear() forces a real read from the database.
    $this->entityManager->clear();
    $updated = $this->newslettersRepository->findOneById($newsletterId);
    $this->assertInstanceOf(NewsletterEntity::class, $updated);
    return $updated;
  }

  public function testItResolvesRawPlaceholdersLeftInAnExistingTemplate() {
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => 'Contact us: {store_address} or {store_email}. Powered by {woocommerce}.',
          ],
        ],
      ],
    ]);

    $this->migration->run();

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    $text = $body['content']['blocks'][0]['text'];

    verify($text)->stringContainsString('New York');
    verify($text)->stringContainsString('10001');
    verify($text)->stringContainsString('store@example.com');
    verify($text)->stringContainsString('<a href="https://woocommerce.com">WooCommerce</a>');
    verify($text)->stringNotContainsString('{store_address}');
    verify($text)->stringNotContainsString('{store_email}');
    verify($text)->stringNotContainsString('{woocommerce}');
  }

  public function testItPreservesSurroundingMarkupWhenResolvingPlaceholders() {
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => '<p style="text-align: center;">{store_address}</p>',
          ],
        ],
      ],
    ]);

    $this->migration->run();

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    $text = $body['content']['blocks'][0]['text'];

    verify($text)->stringStartsWith('<p style="text-align: center;">');
    verify($text)->stringEndsWith('</p>');
    verify($text)->stringContainsString('New York');
    verify($text)->stringContainsString('10001');
    verify($text)->stringNotContainsString('{store_address}');
  }

  public function testItDoesNotRewriteUnrelatedOrderPlaceholdersInMatchedBlocks() {
    // A user-authored block containing both a resolvable token ({site_title}, triggering
    // the walk) and an unrelated literal {order_number} must not have the latter rewritten:
    // order placeholders are only meaningful in live email headings, never in saved content.
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => 'Store: {site_title}. Reference token {order_number} kept as-is.',
          ],
        ],
      ],
    ]);

    $this->migration->run();

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    $text = $body['content']['blocks'][0]['text'];

    verify($text)->stringNotContainsString('{site_title}');
    verify($text)->stringContainsString('{order_number}');
  }

  public function testItDoesNothingWhenNoTemplateSettingIsSaved() {
    $this->migration->run();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $this->assertNull($email);
  }

  public function testItDoesNothingWhenTheSettingPointsAtAMissingNewsletter() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, PHP_INT_MAX);
    $this->migration->run();
    // Would have thrown trying to dereference a non-entity if the guard were missing.
    $this->assertNull($this->newslettersRepository->findOneById(PHP_INT_MAX));
  }

  public function testItLeavesAnAlreadyResolvedTemplateUnchanged() {
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          ['type' => 'text', 'text' => 'Already resolved, no placeholders here.'],
        ],
      ],
    ]);

    $this->migration->run();

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    verify($body['content']['blocks'][0]['text'])->equals('Already resolved, no placeholders here.');
  }

  public function testItIsIdempotentWhenRunTwice() {
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          ['type' => 'text', 'text' => 'Contact us: {store_address}.'],
        ],
      ],
    ]);

    $this->migration->run();
    $this->migration->run();

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    $text = $body['content']['blocks'][0]['text'];

    verify($text)->stringContainsString('New York');
    verify($text)->stringContainsString('10001');
    verify($text)->stringNotContainsString('{store_address}');
  }

  public function testItDoesNothingWhenWooCommerceIsNotActive() {
    $rawText = 'Contact us: {store_address}.';
    $newsletter = $this->createTemplateAndPointSettingAtIt([
      'content' => [
        'blocks' => [
          ['type' => 'text', 'text' => $rawText],
        ],
      ],
    ]);

    $activePlugins = (array)$this->wp->getOption('active_plugins');
    $this->wp->updateOption('active_plugins', array_values(array_diff($activePlugins, ['woocommerce/woocommerce.php'])));
    try {
      // Must not fatal: Helper::wcGetStoreAddress() would otherwise reach into WC_Emails
      // while WooCommerce is considered inactive.
      $this->migration->run();
    } finally {
      $this->wp->updateOption('active_plugins', $activePlugins);
    }

    $body = $this->refetch($newsletter->getId())->getBody();
    $this->assertIsArray($body);
    verify($body['content']['blocks'][0]['text'])->equals($rawText);
  }
}
