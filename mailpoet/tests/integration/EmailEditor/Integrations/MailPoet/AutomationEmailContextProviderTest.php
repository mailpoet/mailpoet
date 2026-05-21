<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\AutomationSendEmailSubjectResolver;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\EmailEditor\Integrations\MailPoet\AutomationEmailContextProvider;
use MailPoet\EmailEditor\Integrations\MailPoet\AutomationEmailPreviewOrderProvider;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class AutomationEmailContextProviderTest extends \MailPoetTest {
  public function testItBuildsRealSendContextFromAutomationRunSubjects(): void {
    $order = new \WC_Order();
    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setMeta([
      'automation' => [
        'run_id' => 123,
      ],
    ]);

    $automationRun = new AutomationRun(1, 1, 'woocommerce:order-completed', [
      new Subject(OrderSubject::KEY, ['order_id' => 456]),
      new Subject('mailpoet:subscriber', ['subscriber_id' => 789]),
    ], 123);
    $automationRunStorage = $this->createMock(AutomationRunStorage::class);
    $automationRunStorage->expects($this->once())
      ->method('getAutomationRun')
      ->with(123)
      ->willReturn($automationRun);

    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->expects($this->once())
      ->method('wcGetOrder')
      ->with(456)
      ->willReturn($order);

    $context = $this->createProvider([
      'automationRunStorage' => $automationRunStorage,
      'wooCommerceHelper' => $wooCommerceHelper,
    ])->build($this->createMock(NewsletterEntity::class), $sendingQueue, false);

    $this->assertSame(['mailpoet:subscriber', OrderSubject::KEY], $context['automation_subject_keys']);
    $this->assertSame($order, $context['order']);
  }

  public function testItBuildsPreviewContextWithSampleOrderAndIgnoresInvalidFilterReturn(): void {
    $order = new \WC_Order();
    $newsletter = $this->createMock(NewsletterEntity::class);
    $newsletter->expects($this->once())
      ->method('getOptionValue')
      ->with(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID)
      ->willReturn(12);

    $automation = $this->createMock(Automation::class);
    $automationStorage = $this->createMock(AutomationStorage::class);
    $automationStorage->expects($this->once())
      ->method('getAutomation')
      ->with(12)
      ->willReturn($automation);

    $subjectResolver = $this->createMock(AutomationSendEmailSubjectResolver::class);
    $subjectResolver->expects($this->once())
      ->method('getGuaranteedSubjectKeysForEmail')
      ->with($automation, $newsletter)
      ->willReturn([OrderSubject::KEY]);

    $previewOrderProvider = $this->createMock(AutomationEmailPreviewOrderProvider::class);
    $previewOrderProvider->expects($this->once())
      ->method('getOrder')
      ->willReturn($order);

    $wp = $this->createMock(WPFunctions::class);
    $wp->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_automation_email_preview_sample_data', [
        'automation_subject_keys' => [OrderSubject::KEY],
        'order' => $order,
      ])
      ->willReturn('invalid');

    $context = $this->createProvider([
      'automationStorage' => $automationStorage,
      'subjectResolver' => $subjectResolver,
      'previewOrderProvider' => $previewOrderProvider,
      'wp' => $wp,
    ])->build($newsletter, null, true);

    $this->assertSame([OrderSubject::KEY], $context['automation_subject_keys']);
    $this->assertSame($order, $context['order']);
  }

  /**
   * @param array{
   *   automationRunStorage?: AutomationRunStorage,
   *   automationStorage?: AutomationStorage,
   *   subjectResolver?: AutomationSendEmailSubjectResolver,
   *   previewOrderProvider?: AutomationEmailPreviewOrderProvider,
   *   wooCommerceHelper?: WooCommerceHelper,
   *   wp?: WPFunctions
   * } $overrides
   */
  private function createProvider(array $overrides): AutomationEmailContextProvider {
    return new AutomationEmailContextProvider(
      $overrides['automationRunStorage'] ?? $this->createMock(AutomationRunStorage::class),
      $overrides['automationStorage'] ?? $this->createMock(AutomationStorage::class),
      $overrides['subjectResolver'] ?? $this->createMock(AutomationSendEmailSubjectResolver::class),
      $overrides['previewOrderProvider'] ?? $this->createMock(AutomationEmailPreviewOrderProvider::class),
      $overrides['wooCommerceHelper'] ?? $this->createMock(WooCommerceHelper::class),
      $overrides['wp'] ?? $this->createMock(WPFunctions::class)
    );
  }
}
