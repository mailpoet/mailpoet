<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Hooks;

use Codeception\Stub\Expected;
use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Hooks\CreateAutomationRunHook;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Test\DataFactories;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetTest;

class CreateAutomationRunHookTest extends MailPoetTest {
  private const TEST_SUBSCRIBER_ID = 123;

  public function testWithoutPreviousRuns(): void {
    $service = $this->diContainer->get(CreateAutomationRunHook::class);

    // with run once args
    $args = $this->getStepRunArgs(true);
    $this->assertFalse($service->createAutomationRun(false, $args));
    $this->assertTrue($service->createAutomationRun(true, $args));

    // without run once args
    $args = $this->getStepRunArgs(false);
    $this->assertFalse($service->createAutomationRun(false, $args));
    $this->assertTrue($service->createAutomationRun(true, $args));
  }

  public function testWithPreviousRuns(): void {
    $service = $this->diContainer->get(CreateAutomationRunHook::class);

    // with run once args
    $args = $this->getStepRunArgs(true);
    $this->createAutomationRun($args->getAutomation());
    $this->assertFalse($service->createAutomationRun(false, $args));
    $this->assertFalse($service->createAutomationRun(true, $args));

    // without run once args
    $args = $this->getStepRunArgs(false);
    $this->createAutomationRun($args->getAutomation());
    $this->assertFalse($service->createAutomationRun(false, $args));
    $this->assertTrue($service->createAutomationRun(true, $args));
  }

  public function testItAcquiresLock(): void {
    $service = $this->diContainer->get(CreateAutomationRunHook::class);

    $args = $this->getStepRunArgs(true);
    $this->assertFalse($service->createAutomationRun(false, $args));
    $this->assertTrue($service->createAutomationRun(true, $args));

    // next run should be blocked by the lock
    $this->assertFalse($service->createAutomationRun(true, $args));

    // check lock
    $wp = $this->diContainer->get(WPFunctions::class);
    $subject = array_values($args->getAutomationRun()->getSubjects(SubscriberSubject::KEY))[0];
    $key = sprintf('mailpoet:run-once-per-subscriber:[%s][%s]', $args->getAutomation()->getId(), $subject->getHash());
    $lock = $wp->getTransient($key);
    $timeout = new DateTimeImmutable('@' . $wp->getOption("_transient_timeout_$key"));

    $this->assertIsString($lock);
    $this->assertNotEmpty($lock);
    $this->assertGreaterThan(new DateTimeImmutable('+10 seconds'), $timeout);
    $this->assertLessThan(new DateTimeImmutable('+2 minutes'), $timeout);
  }

  public function testItVerifiesLock(): void {
    $wp = $this->diContainer->get(WPFunctions::class);
    $args = $this->getStepRunArgs(true);
    $subject = array_values($args->getAutomationRun()->getSubjects(SubscriberSubject::KEY))[0];
    $key = sprintf('mailpoet:run-once-per-subscriber:[%s][%s]', $args->getAutomation()->getId(), $subject->getHash());

    $service = $this->getServiceWithOverrides(CreateAutomationRunHook::class, [
      'automationRunStorage' => $this->make(AutomationRunStorage::class, [
        'getCountByAutomationAndSubject' => Expected::once(function () use ($wp, $key): int {
          // simulate that another process caused a locking race condition
          $wp->setTransient($key, 'lock-value-from-another-process', MINUTE_IN_SECONDS);
          return 0;
        }),
      ]),
    ]);

    // run will be blocked by the lock due to a lock value mismatch
    $this->assertFalse($service->createAutomationRun(true, $args));
  }

  private function getStepRunArgs(bool $runOncePerSubscriber): StepRunArgs {
    $automation = (new DataFactories\Automation())
      ->withMeta('mailpoet:run-once-per-subscriber', $runOncePerSubscriber)
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->create();

    $subject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => self::TEST_SUBSCRIBER_ID]);
    $automationRun = new AutomationRun($automation->getId(), 1, '', [$subject]);

    $trigger = array_values($automation->getTriggers())[0];
    $subscriberEntry = new SubjectEntry($this->diContainer->get(SubscriberSubject::class), $subject);
    return new StepRunArgs($automation, $automationRun, $trigger, [$subscriberEntry], 1);
  }

  private function createAutomationRun(Automation $automation): AutomationRun {
    return (new DataFactories\AutomationRun())
      ->withAutomation($automation)
      ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => self::TEST_SUBSCRIBER_ID]))
      ->create();
  }
}
