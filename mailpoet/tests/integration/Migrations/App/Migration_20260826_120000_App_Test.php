<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
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

  /** @var WPFunctions */
  private $wp;

  /** @var array */
  private $originalWcOptions = [];

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260826_120000_App($this->diContainer);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
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

  public function testItResolvesRawPlaceholdersLeftInAnExistingTemplate() {
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL)
      ->withBody([
        'content' => [
          'blocks' => [
            [
              'type' => 'text',
              'text' => 'Contact us: {store_address} or {store_email}. Powered by {woocommerce}.',
            ],
          ],
        ],
      ])
      ->create();

    $this->migration->run();

    $updated = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updated);
    $body = $updated->getBody();
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
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL)
      ->withBody([
        'content' => [
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<p style="text-align: center;">{store_address}</p>',
            ],
          ],
        ],
      ])
      ->create();

    $this->migration->run();

    $updated = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updated);
    $body = $updated->getBody();
    $this->assertIsArray($body);
    $text = $body['content']['blocks'][0]['text'];

    verify($text)->stringStartsWith('<p style="text-align: center;">');
    verify($text)->stringEndsWith('</p>');
    verify($text)->stringContainsString('New York');
    verify($text)->stringContainsString('10001');
    verify($text)->stringNotContainsString('{store_address}');
  }

  public function testItDoesNothingWhenNoWCTransactionalTemplateExists() {
    $this->migration->run();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $this->assertNull($email);
  }

  public function testItLeavesAnAlreadyResolvedTemplateUnchanged() {
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL)
      ->withBody([
        'content' => [
          'blocks' => [
            ['type' => 'text', 'text' => 'Already resolved, no placeholders here.'],
          ],
        ],
      ])
      ->create();

    $this->migration->run();

    $updated = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updated);
    $body = $updated->getBody();
    $this->assertIsArray($body);
    verify($body['content']['blocks'][0]['text'])->equals('Already resolved, no placeholders here.');
  }
}
