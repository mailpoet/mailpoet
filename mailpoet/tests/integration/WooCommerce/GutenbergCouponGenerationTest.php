<?php declare(strict_types = 1);

namespace MailPoet\Test\WooCommerce;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer;
use MailPoet\NewsletterProcessingException;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WooCommerce\GutenbergCouponGenerationFailureCollector;
use MailPoet\WooCommerce\GutenbergCouponGenerator;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class GutenbergCouponGenerationTest extends \MailPoetTest {
  /** @var int[] */
  private $postIds = [];

  private GutenbergCouponGenerator $generator;

  public function _before() {
    parent::_before();
    $this->generator = $this->diContainer->get(GutenbergCouponGenerator::class);
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
    $newsletter = $this->createGutenbergNewsletter(
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

  public function testItBlocksNonAutomationCreateNewCouponBeforeRender(): void {
    $newsletter = $this->createGutenbergNewsletter(
      NewsletterEntity::TYPE_STANDARD,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $this->expectException(NewsletterProcessingException::class);
    $this->expectExceptionMessage(NewsletterTask::GUTENBERG_COUPON_UNSUPPORTED_SEND_ERROR);

    (new NewsletterTask())->preProcessNewsletter($newsletter, $task);
  }

  public function testItBlocksMultiRecipientAutomationBeforeRender(): void {
    $newsletter = $this->createGutenbergNewsletter(
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
      $this->assertSame(NewsletterTask::GUTENBERG_COUPON_UNSUPPORTED_SEND_ERROR, $e->getMessage());
    }

    $this->diContainer->get(NewslettersRepository::class)->refresh($newsletter);
    $this->diContainer->get(SendingQueuesRepository::class)->refresh($queue);
    $this->assertSame(NewsletterEntity::STATUS_CORRUPT, $newsletter->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertNull($queue->getNewsletterRenderedBody());
    $this->assertCount(0, get_posts(['post_type' => 'shop_coupon', 'post_status' => 'any', 'numberposts' => -1]));
  }

  public function testGenerationFailureStopsBeforeRenderedBodyPersistence(): void {
    $newsletter = $this->createGutenbergNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      $this->createCouponBlockContent(['discountType' => 'percent', 'amount' => '15']),
      1
    );
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $collector = $this->diContainer->get(GutenbergCouponGenerationFailureCollector::class);
    $forcedFailure = function(array $context) use ($collector): array {
      $collector->record('forced_failure', 'Forced generation failure.', [], $context);
      return $context;
    };
    WPFunctions::get()->addFilter('woocommerce_email_editor_rendering_email_context', $forcedFailure, 20, 1);

    try {
      (new NewsletterTask())->preProcessNewsletter($newsletter, $task);
      $this->fail('Expected collected coupon generation failure to stop preprocessing.');
    } catch (NewsletterProcessingException $e) {
      $this->assertSame(NewsletterTask::GUTENBERG_COUPON_UNSUPPORTED_SEND_ERROR, $e->getMessage());
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
    $newsletter = $this->createGutenbergNewsletter(
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

  private function createGutenbergNewsletter(string $type, string $postContent, int $subscriberCount): NewsletterEntity {
    $postId = wp_insert_post([
      'post_type' => 'mailpoet_email',
      'post_status' => 'publish',
      'post_title' => 'Coupon email',
      'post_content' => $postContent,
    ]);
    $this->assertIsInt($postId);
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
    $attrsJson = wp_json_encode($attrs);
    return sprintf(
      '<!-- wp:woocommerce/coupon-code %1$s --><span class="woocommerce-coupon-code">%2$s</span><!-- /wp:woocommerce/coupon-code -->',
      $attrsJson,
      GutenbergCouponGenerator::SAFE_PLACEHOLDER
    );
  }
}
