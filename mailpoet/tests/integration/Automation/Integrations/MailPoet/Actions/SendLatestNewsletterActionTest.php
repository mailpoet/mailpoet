<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendLatestNewsletterAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;

class SendLatestNewsletterActionTest extends \MailPoetTest {
  private SendLatestNewsletterAction $action;

  private Automation $automation;

  public function _before() {
    parent::_before();
    $this->action = $this->diContainer->get(SendLatestNewsletterAction::class);
    $this->automation = new Automation('test-automation', [], new \WP_User());
  }

  public function testItReturnsRequiredSubjects(): void {
    $this->assertSame(['mailpoet:subscriber', 'mailpoet:segment'], $this->action->getSubjectKeys());
  }

  public function testItAllowsEmptyArgs(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, [], []);

    $this->action->validate(new StepValidationArgs($this->automation, $step, [
      $this->diContainer->get(SubscriberSubject::class),
      $this->diContainer->get(SegmentSubject::class),
    ]));

    $this->assertSame([], $step->getArgs());
  }

  public function testItRejectsNonEmptyArgsWithGeneralError(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, ['email_id' => 1], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, [
        $this->diContainer->get(SubscriberSubject::class),
        $this->diContainer->get(SegmentSubject::class),
      ]));
    } catch (ValidationException $error) {
      $this->assertArrayHasKey('general', $error->getErrors());
    }

    $this->assertNotNull($error);
  }

  public function testItRequiresSegmentSubject(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, [], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, [
        $this->diContainer->get(SubscriberSubject::class),
      ]));
    } catch (ValidationException $error) {
      $this->assertArrayHasKey('general', $error->getErrors());
    }

    $this->assertNotNull($error);
  }
}
