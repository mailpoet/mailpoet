<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\Coupons;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockGenerationFailureCollector;
use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockGenerator;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer;
use MailPoet\NewsletterProcessingException;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class CouponBlockGenerationTest extends \MailPoetTest {
  private const COUPON_BLOCK_UNSUPPORTED_SEND_ERROR = 'Auto-generated coupon codes are only supported in regular newsletters and automation emails sent to one subscriber at a time. Remove the generated coupon block or use an existing coupon before sending this email.';
  private const COUPON_BLOCK_RECIPIENT_RESTRICTION_UNSUPPORTED_SEND_ERROR = 'Recipient-restricted generated coupons are only supported in automation emails sent to one subscriber at a time. Disable recipient restriction, remove the generated coupon block, or use an existing coupon before sending this email.';

  /** @var int[] */
  private $postIds = [];

  private CouponBlockGenerator $generator;

  public function _before() {
    parent::_before();
    $this->generator = $this->diContainer->get(CouponBlockGenerator::class);
    $this->generator->init();
  }

  public function _after() {
    WPFunctions::get()->removeFilter('woocommerce_coupon_code_block_auto_generate', [$this->generator, 'generate'], 5);
    foreach ($this->postIds as $postId) {
      wp_delete_post($postId, true);
    }
    foreach (get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]) as $couponPost) {
      wp_delete_post($couponPost->ID, true);
    }
    parent::_after();
  }

  public function testItAllowsSupportedOneRecipientAutomationQueuesToRender(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      $this->createCouponBlockContent([
        'source' => 'createNew',
        'discountType' => 'percent',
        'amount' => '15',
        'restrictToSubscriber' => true,
      ]),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    (new NewsletterTask())->preProcessNewsletter($newsletter, $task);

    $body = $queue->getNewsletterRenderedBody();
    $this->assertIsArray($body);
    $this->assertSame(NewsletterEntity::STATUS_ACTIVE, $newsletter->getStatus());
  }

  public function testItAllowsStandardNewslettersWithGeneratedCouponBlocksToRender(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_STANDARD,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      2
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    (new NewsletterTask())->preProcessNewsletter($newsletter, $task);

    $body = $queue->getNewsletterRenderedBody();
    $this->assertIsArray($body);
    $this->assertSame(NewsletterEntity::STATUS_ACTIVE, $newsletter->getStatus());
  }

  public function testItGeneratesWooCommerceCouponWhenWooCommerceOmitsDefaultAttributes(): void {
    $context = new Rendering_Context(new \WP_Theme_JSON(), [
      'integration' => 'mailpoet',
      'newsletter_id' => 1,
      'queue_id' => 2,
      'email_type' => NewsletterEntity::TYPE_STANDARD,
      'is_real_send' => true,
      'is_preview' => false,
      'is_single_recipient' => false,
      'subscriber_count' => 2,
      'mailpoet_is_automation' => false,
    ]);

    $couponCode = WPFunctions::get()->applyFilters(
      'woocommerce_coupon_code_block_auto_generate',
      '',
      [],
      $context
    );

    $this->assertIsString($couponCode);
    $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{6}-[A-Z0-9]{4}$/', $couponCode);
    $this->assertGreaterThan(0, wc_get_coupon_id_by_code($couponCode));
  }

  public function testItBlocksRecipientRestrictedStandardNewsletterBeforeRender(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_STANDARD,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15', 'restrictToSubscriber' => true]),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $this->expectException(NewsletterProcessingException::class);
    $this->expectExceptionMessage(self::COUPON_BLOCK_RECIPIENT_RESTRICTION_UNSUPPORTED_SEND_ERROR);

    (new NewsletterTask())->preProcessNewsletter($newsletter, $task);
  }

  public function testItBlocksMultiRecipientAutomationBeforeRender(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      2
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    try {
      (new NewsletterTask())->preProcessNewsletter($newsletter, $task);
      $this->fail('Expected coupon generation preflight to stop preprocessing.');
    } catch (NewsletterProcessingException $e) {
      $this->assertSame(self::COUPON_BLOCK_UNSUPPORTED_SEND_ERROR, $e->getMessage());
    }

    $this->diContainer->get(NewslettersRepository::class)->refresh($newsletter);
    $this->diContainer->get(SendingQueuesRepository::class)->refresh($queue);
    $this->assertSame(NewsletterEntity::STATUS_CORRUPT, $newsletter->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertNull($queue->getNewsletterRenderedBody());
    $this->assertCount(0, get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]));
  }

  public function testGenerationFailureStopsBeforeRenderedBodyPersistence(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $collector = $this->diContainer->get(CouponBlockGenerationFailureCollector::class);
    $forcedFailure = function(array $context) use ($collector): array {
      $collector->record('forced_failure', 'Forced generation failure.', [], $context);
      return $context;
    };
    WPFunctions::get()->addFilter('woocommerce_email_editor_rendering_email_context', $forcedFailure, 20, 1);

    try {
      (new NewsletterTask())->preProcessNewsletter($newsletter, $task);
      $this->fail('Expected collected coupon generation failure to stop preprocessing.');
    } catch (NewsletterProcessingException $e) {
      $this->assertSame('Auto-generated coupon code could not be created: Forced generation failure.', $e->getMessage());
    } finally {
      WPFunctions::get()->removeFilter('woocommerce_email_editor_rendering_email_context', $forcedFailure, 20);
    }

    $this->diContainer->get(NewslettersRepository::class)->refresh($newsletter);
    $this->diContainer->get(SendingQueuesRepository::class)->refresh($queue);
    $this->assertSame(NewsletterEntity::STATUS_CORRUPT, $newsletter->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertNull($queue->getNewsletterRenderedBody());
    $this->assertCount(0, get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]));
  }

  public function testItDoesNotGenerateCouponsForPreviewOrViewInBrowserRenders(): void {
    $newsletter = $this->createBlockEmailNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $renderer = $this->diContainer->get(\MailPoet\Newsletter\Renderer\Renderer::class);
    $renderer->renderAsPreview($newsletter, 'html');
    $this->diContainer->get(ViewInBrowserRenderer::class)->render(false, $newsletter, null, $queue);

    $this->assertCount(0, get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]));
  }

  private function createBlockEmailNewsletter(string $type, string $postContent, int $subscriberCount): NewsletterEntity {
    $postId = wp_insert_post([
      'post_type' => 'mailpoet_email',
      'post_status' => 'publish',
      'post_title' => 'Coupon email',
      'post_content' => $postContent,
    ]);
    $this->assertIsInt($postId);
    $this->assertGreaterThan(0, $postId, 'Failed to create mailpoet_email post.');
    $this->postIds[] = $postId;

    $factory = (new NewsletterFactory())
      ->withType($type)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withWpPostId($postId)
      ->withScheduledQueue(['count_processed' => 0, 'count_total' => $subscriberCount]);

    for ($i = 0; $i < $subscriberCount; $i++) {
      $factory->withSubscriber((new SubscriberFactory())->withEmail("subscriber-{$i}@example.com")->create());
    }

    $newsletter = $factory->create();
    $queue = $newsletter->getLatestQueue();
    $task = $queue ? $queue->getTask() : null;
    if ($queue && $task) {
      $this->entityManager->refresh($task);
      $task->setSendingQueue($queue);
    }
    return $newsletter;
  }

  private function createCouponBlockContent(array $attrs): string {
    $attrsJson = $attrs ? ' ' . wp_json_encode($attrs) : '';
    return sprintf(
      '<!-- wp:woocommerce/coupon-code%1$s --><div class="wp-block-woocommerce-coupon-code"><strong>%2$s</strong></div><!-- /wp:woocommerce/coupon-code -->',
      $attrsJson,
      CouponBlockGenerator::SAFE_PLACEHOLDER
    );
  }
}
