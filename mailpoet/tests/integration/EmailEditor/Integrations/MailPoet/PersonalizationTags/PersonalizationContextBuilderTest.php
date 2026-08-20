<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationContextBuilder;
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

class PersonalizationContextBuilderTest extends \MailPoetTest {
  private PersonalizationContextBuilder $builder;
  private NewsletterEntity $newsletter;
  private SubscriberEntity $subscriber;
  private SendingQueueEntity $queue;

  public function _before() {
    parent::_before();
    $this->builder = $this->diContainer->get(PersonalizationContextBuilder::class);
    $this->newsletter = (new NewsletterFactory())->withAutomationType()->create();
    $this->subscriber = (new SubscriberFactory())->withEmail('recipient@example.com')->create();
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->queue = (new SendingQueueFactory())->create($task, $this->newsletter);
  }

  public function testItBuildsRecipientContextWithoutAutomationRun(): void {
    $context = $this->builder->build($this->newsletter, $this->subscriber, $this->queue);

    $this->assertSame('recipient@example.com', $context['recipient_email']);
    $this->assertSame($this->newsletter->getId(), $context['newsletter_id']);
    $this->assertSame($this->queue->getId(), $context['queue_id']);
    $this->assertFalse($context['is_preview']);
    $this->assertArrayNotHasKey('order', $context);
  }

  public function testItPassesPreviewFlag(): void {
    $context = $this->builder->build($this->newsletter, $this->subscriber, $this->queue, true);
    $this->assertTrue($context['is_preview']);
  }

  /**
   * @group woo
   */
  public function testItLoadsOrderFromAutomationRunAndExtendsTags(): void {
    $order = $this->tester->createWooCommerceOrder();
    $run = (new AutomationRunFactory())
      ->withSubject(new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]))
      ->create();
    $this->queue->setMeta(['automation' => ['run_id' => $run->getId()]]);
    $this->entityManager->flush();

    $wp = WPFunctions::get();
    $filteredSubjects = null;
    $wp->addFilter('mailpoet_automation_email_personalization_context', function (array $context, array $subjects) use (&$filteredSubjects): array {
      $filteredSubjects = array_keys($subjects);
      $context['custom'] = 'value';
      return $context;
    }, 10, 2);
    $extendedSubjects = null;
    $wp->addAction('mailpoet_automation_email_extend_personalization_tags_for_sending', function (array $subjects) use (&$extendedSubjects): void {
      $extendedSubjects = $subjects;
    });

    try {
      $context = $this->builder->build($this->newsletter, $this->subscriber, $this->queue);
    } finally {
      $wp->removeAllFilters('mailpoet_automation_email_personalization_context');
      $wp->removeAllActions('mailpoet_automation_email_extend_personalization_tags_for_sending');
      $this->tester->deleteTestWooOrder($order->get_id());
    }

    $this->assertInstanceOf(\WC_Order::class, $context['order']);
    $this->assertSame($order->get_id(), $context['order']->get_id());
    $this->assertSame('value', $context['custom']);
    $this->assertSame([OrderSubject::KEY], $filteredSubjects);
    $this->assertSame([OrderSubject::KEY], $extendedSubjects);
  }
}
