<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\AutomationRun as AutomationRunFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class PersonalizationTagLinkResolverTest extends \MailPoetTest {
  private PersonalizationTagLinkResolver $resolver;
  private Personalization_Tags_Registry $registry;
  private NewsletterEntity $newsletter;
  private SubscriberEntity $subscriber;
  private SendingQueueEntity $queue;

  public function _before() {
    parent::_before();
    $this->resolver = $this->diContainer->get(PersonalizationTagLinkResolver::class);
    $this->registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $this->newsletter = (new NewsletterFactory())->withAutomationType()->create();
    $this->subscriber = (new SubscriberFactory())->withEmail('recipient@example.com')->create();
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->queue = (new SendingQueueFactory())->create($task, $this->newsletter);
  }

  public function _after() {
    $this->registry->unregister('[acme/test-url]');
    parent::_after();
  }

  public function testItRecognizesTokenUrlsBySyntax(): void {
    $this->assertTrue($this->resolver->isTokenUrl('[mailpoet/subscription-unsubscribe-url]'));
    // registered only once automation subjects are known, so detection can't depend on the registry
    $this->assertTrue($this->resolver->isTokenUrl('[woocommerce/order-review-url]'));
    $this->assertTrue($this->resolver->isTokenUrl('[acme/not-registered]'));
    $this->assertFalse($this->resolver->isTokenUrl('[link:subscription_unsubscribe_url]'));
    $this->assertFalse($this->resolver->isTokenUrl('[postLink]'));
    $this->assertFalse($this->resolver->isTokenUrl('https://example.com/?q=[mailpoet/subscription-unsubscribe-url]'));
    $this->assertFalse($this->resolver->isTokenUrl('[acme/with-args key="value"]'));
    $this->assertFalse($this->resolver->isTokenUrl(''));
  }

  public function testItReturnsTagDisplayName(): void {
    $this->registry->register($this->createTag(function (): string {
      return 'https://example.com';
    }));

    $this->assertSame('Test URL', $this->resolver->getDisplayName('[acme/test-url]'));
    $this->assertNull($this->resolver->getDisplayName('https://example.com'));
  }

  public function testItResolvesTokenUrlWithRecipientContext(): void {
    $receivedContext = null;
    $this->registry->register($this->createTag(function (array $context) use (&$receivedContext): string {
      $receivedContext = $context;
      return 'https://example.com/for/' . $context['recipient_email'];
    }));

    $url = $this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue);

    $this->assertSame('https://example.com/for/recipient@example.com', $url);
    $this->assertIsArray($receivedContext);
    $this->assertSame($this->queue->getId(), $receivedContext['queue_id']);
    $this->assertSame(Personalizer::RENDERING_CONTEXT_HREF, $receivedContext[Personalizer::RENDERING_CONTEXT_KEY]);
  }

  public function testItLooksTagUpOnlyAfterContextBuildRegisteredIt(): void {
    $run = (new AutomationRunFactory())->create();
    $this->queue->setMeta(['automation' => ['run_id' => $run->getId()]]);
    $this->entityManager->flush();
    $wp = WPFunctions::get();
    $wp->addAction('mailpoet_automation_email_extend_personalization_tags_for_sending', function () {
      $this->registry->register($this->createTag(function (array $context): string {
        return 'https://example.com/late/' . ($context['is_preview'] ? 'preview' : 'live');
      }));
    });

    try {
      $this->assertNull($this->registry->get_by_token('[acme/test-url]'));
      $url = $this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue);
      $this->assertSame('https://example.com/late/live', $url);
      $this->registry->unregister('[acme/test-url]');
      $previewUrl = $this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue, true);
      $this->assertSame('https://example.com/late/preview', $previewUrl);
    } finally {
      $wp->removeAllActions('mailpoet_automation_email_extend_personalization_tags_for_sending');
    }
  }

  public function testItResolvesMarkdownLinkTargetsInText(): void {
    $this->registry->register($this->createTag(function (): string {
      return 'https://example.com/resolved';
    }));
    $context = ['recipient_email' => 'recipient@example.com'];

    // canonical token (after link tracking) and the raw renderer form (preview, no tracking)
    $text = "[Go]([acme/test-url]) [Raw](http://[acme/test-url%5D) [Dead]([acme/not-registered]) [Keep](https://example.com/?a=[x]) [Legacy]([link:subscription_manage_url])";
    $this->assertSame(
      "[Go](https://example.com/resolved) [Raw](https://example.com/resolved) [Dead]() [Keep](https://example.com/?a=[x]) [Legacy]([link:subscription_manage_url])",
      $this->resolver->resolveMarkdownLinks($text, $context)
    );
  }

  public function testItResolvesMailPoetLinkTags(): void {
    $url = $this->resolver->resolve('[mailpoet/subscription-unsubscribe-url]', $this->newsletter, $this->subscriber, $this->queue);

    $this->assertIsString($url);
    $this->assertStringContainsString('action=confirm_unsubscribe', $url);
  }

  public function testItReturnsNullForUnregisteredOrEmptyOrFailingTags(): void {
    $this->assertNull($this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue));

    $this->registry->register($this->createTag(function (): string {
      return '';
    }));
    $this->assertNull($this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue));

    $this->registry->unregister('[acme/test-url]');
    $this->registry->register($this->createTag(function (): string {
      throw new \RuntimeException('broken');
    }));
    $this->assertNull($this->resolver->resolve('[acme/test-url]', $this->newsletter, $this->subscriber, $this->queue));
  }

  private function createTag(callable $callback): Personalization_Tag {
    return new Personalization_Tag('Test URL', 'acme/test-url', 'Test', $callback);
  }
}
