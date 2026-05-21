<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\AutomationSendEmailSubjectResolver;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoetUnitTest;

class AutomationSendEmailSubjectResolverTest extends MailPoetUnitTest {
  public function testItReturnsGuaranteedOrderSubjectForOrderOnlyPath(): void {
    $resolver = $this->createResolver([
      'woocommerce:order-completed' => [OrderSubject::KEY, 'mailpoet:subscriber'],
    ]);
    $sendEmail = new Step('send', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
    $automation = $this->createAutomation([
      new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('trigger')]),
      new Step('trigger', Step::TYPE_TRIGGER, 'woocommerce:order-completed', [], [new NextStep('send')]),
      $sendEmail,
    ]);

    $this->assertSame(
      ['mailpoet:subscriber', OrderSubject::KEY],
      $resolver->getGuaranteedSubjectKeysForStep($automation, $sendEmail)
    );
  }

  public function testItIntersectsSubjectsFromAllTriggersThatReachTheSendStep(): void {
    $resolver = $this->createResolver([
      'woocommerce:order-completed' => [OrderSubject::KEY, 'mailpoet:subscriber'],
      'mailpoet:someone-subscribes' => ['mailpoet:subscriber'],
    ]);
    $sendEmail = new Step('send', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
    $automation = $this->createAutomation([
      new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('order-trigger'), new NextStep('subscriber-trigger')]),
      new Step('order-trigger', Step::TYPE_TRIGGER, 'woocommerce:order-completed', [], [new NextStep('send')]),
      new Step('subscriber-trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-subscribes', [], [new NextStep('send')]),
      $sendEmail,
    ]);

    $this->assertSame(
      ['mailpoet:subscriber'],
      $resolver->getGuaranteedSubjectKeysForStep($automation, $sendEmail)
    );
  }

  public function testItReturnsNoGuaranteedSubjectsWhenReachableTriggerIsUnknown(): void {
    $resolver = $this->createResolver([
      'woocommerce:order-completed' => [OrderSubject::KEY, 'mailpoet:subscriber'],
    ]);
    $sendEmail = new Step('send', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
    $automation = $this->createAutomation([
      new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('known-trigger'), new NextStep('unknown-trigger')]),
      new Step('known-trigger', Step::TYPE_TRIGGER, 'woocommerce:order-completed', [], [new NextStep('send')]),
      new Step('unknown-trigger', Step::TYPE_TRIGGER, 'unknown:trigger', [], [new NextStep('send')]),
      $sendEmail,
    ]);

    $this->assertSame(
      [],
      $resolver->getGuaranteedSubjectKeysForStep($automation, $sendEmail)
    );
  }

  public function testSharedEmailRequiresEveryReferencingStepToHaveOrderSubject(): void {
    $resolver = $this->createResolver([
      'woocommerce:order-completed' => [OrderSubject::KEY, 'mailpoet:subscriber'],
      'mailpoet:someone-subscribes' => ['mailpoet:subscriber'],
    ]);
    $newsletter = $this->make(NewsletterEntity::class, [
      'getId' => 99,
      'getWpPostId' => 123,
      'getOptionValue' => null,
    ]);
    $automation = $this->createAutomation([
      new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('order-trigger'), new NextStep('subscriber-trigger')]),
      new Step('order-trigger', Step::TYPE_TRIGGER, 'woocommerce:order-completed', [], [new NextStep('order-send')]),
      new Step('subscriber-trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-subscribes', [], [new NextStep('subscriber-send')]),
      new Step('order-send', Step::TYPE_ACTION, SendEmailAction::KEY, ['email_id' => 99], []),
      new Step('subscriber-send', Step::TYPE_ACTION, SendEmailAction::KEY, ['email_id' => 99], []),
    ]);

    $this->assertSame(
      ['mailpoet:subscriber'],
      $resolver->getGuaranteedSubjectKeysForEmail($automation, $newsletter)
    );
  }

  private function createResolver(array $subjectKeysByTrigger): AutomationSendEmailSubjectResolver {
    $registry = $this->make(Registry::class, [
      'getTrigger' => function (string $key) use ($subjectKeysByTrigger): ?Trigger {
        if (!array_key_exists($key, $subjectKeysByTrigger)) {
          return null;
        }
        return $this->makeEmpty(Trigger::class, [
          'getKey' => $key,
        ]);
      },
    ]);
    $subjectTransformerHandler = $this->make(SubjectTransformerHandler::class, [
      'getSubjectKeysForTrigger' => function (Trigger $trigger) use ($subjectKeysByTrigger): array {
        return $subjectKeysByTrigger[$trigger->getKey()] ?? [];
      },
      'getSubjectKeysForAutomation' => [],
    ]);

    return new AutomationSendEmailSubjectResolver($registry, $subjectTransformerHandler);
  }

  /** @param Step[] $steps */
  private function createAutomation(array $steps): Automation {
    $stepMap = [];
    foreach ($steps as $step) {
      $stepMap[$step->getId()] = $step;
    }
    $triggerSteps = array_filter($stepMap, function (Step $step): bool {
      return $step->getType() === Step::TYPE_TRIGGER;
    });

    return $this->make(Automation::class, [
      'getSteps' => $stepMap,
      'getTriggers' => $triggerSteps,
      'getStep' => function (string $stepId) use ($stepMap): ?Step {
        return $stepMap[$stepId] ?? null;
      },
    ]);
  }
}
