<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\AutomationTimeSpanController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;

class AutomationTimeSpanControllerTest extends \MailPoetTest {

  /** @var AutomationTimeSpanController */
  private $testee;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var AutomationStorage */
  private $automationStorage;

  public function _before() {
    $this->testee = $this->diContainer->get(AutomationTimeSpanController::class);
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
  }

  public function testItReturnsNoEmailWhenNoEmailStepExist() {
    $automation = $this->tester->createAutomation('test');
    $this->createEmail();
    $emails = $this->testee->getEmailsFromAutomations([$automation]);
    $this->assertEmpty($emails);
  }

  public function testItReturnsEmailsWhenEmailStepsExist() {
    $newsletter1 = $this->createEmail();
    $newsletter2 = $this->createEmail();
    $this->createEmail();

    $trigger = new Step(
      'trigger',
      Step::TYPE_TRIGGER,
      'trigger',
      [],
      [
        new NextStep('first-email'),
      ]
    );
    $firstEmail = new Step(
      'first-email',
      Step::TYPE_ACTION,
      SendEmailAction::KEY,
      ['email_id' => $newsletter1->getId()],
      [
        new NextStep('second-email'),
      ]
    );
    $secondEmail = new Step(
      'second-email',
      Step::TYPE_ACTION,
      SendEmailAction::KEY,
      ['email_id' => $newsletter2->getId()],
      []
    );
    $automation = $this->tester->createAutomation('test', $trigger, $firstEmail, $secondEmail);

    $emails = $this->testee->getEmailsFromAutomations([$automation]);
    $this->assertCount(2, $emails);
    $expectedIds = [$newsletter1->getId(), $newsletter2->getId()];
    $actualIds = array_map(function (NewsletterEntity $newsletter) {
      return $newsletter->getId();
    }, $emails);
    $this->assertEquals($expectedIds, $actualIds);
  }

  public function testItReturnsTheEmailsFromTheCorrectTimespan() {
    global $wpdb;
    $after = new \DateTimeImmutable('2022-01-01 00:00:00');
    $before = new \DateTimeImmutable('2022-02-02 00:00:00');
    $automation = $this->tester->createAutomation('test');
    $emailBefore = $this->createEmail('emailBefore');
    $emailInTimeSpan = $this->createEmail('emailInTimeSpan');
    $emailInTimeSpan2 = $this->createEmail('emailInTimeSpan2');
    $emailAfter = $this->createEmail('emailAfter');

    $root = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [
        new NextStep('trigger'),
      ]
    );
    $trigger = new Step(
      'trigger',
      Step::TYPE_TRIGGER,
      'trigger',
      [],
      [
        new NextStep('first-email'),
      ]
    );

    // Create versions
    $emailsCreatedAtMap = [
      [
        'email' => $emailBefore,
        'date' => new \DateTimeImmutable('2021-12-02 00:00:00'),
      ],
      [
        /**
         * This email is in the timespan as it could have been sent in the timespan
         * although the creation of the version was before the timespan.
         */
        'email' => $emailInTimeSpan,
        'date' => new \DateTimeImmutable('2021-12-31 00:00:00'),
      ],
      [
        'email' => $emailInTimeSpan2,
        'date' => new \DateTimeImmutable('2022-01-03 00:00:00'),
      ],
      [
        'email' => $emailAfter,
        'date' => new \DateTimeImmutable('2022-06-02 00:00:00'),
      ],
    ];
    foreach ($emailsCreatedAtMap as $emailCreatedAt) {
      $email = $emailCreatedAt['email'];

      $emailStep = new Step(
        'first-email',
        Step::TYPE_ACTION,
        SendEmailAction::KEY,
        ['email_id' => $email->getId()],
        [
          new NextStep('second-email'),
        ]
      );
      $automation->setSteps([$root, $trigger, $emailStep]);
      $this->automationStorage->updateAutomation($automation);
      $automation = $this->automationStorage->getAutomation($automation->getId());
      $this->assertInstanceOf(Automation::class, $automation);

      // Update the created_at value of the version
      $sql = 'update ' . $wpdb->prefix . 'mailpoet_automation_versions set created_at=%s where id=%d';
      $sql = $wpdb->prepare(
        $sql,
        $emailCreatedAt['date']->format('Y-m-d H:i:s'),
        $automation->getVersionId()
      );
      $this->assertEquals(1, $wpdb->query($sql));
    }

    $emails = $this->testee->getAutomationEmailsInTimeSpan($automation, $after, $before);
    $this->assertCount(2, $emails);
    $expectedIds = [$emailInTimeSpan->getId(), $emailInTimeSpan2->getId()];
    $actualIds = array_map(function (NewsletterEntity $newsletter) {
      return $newsletter->getId();
    }, $emails);
    $this->assertEquals($expectedIds, $actualIds);
  }

  private function createEmail(string $subject = 'subject'): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setSubject($subject);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->newsletterRepository->persist($newsletter);
    $this->newsletterRepository->flush();
    return $newsletter;
  }
}
