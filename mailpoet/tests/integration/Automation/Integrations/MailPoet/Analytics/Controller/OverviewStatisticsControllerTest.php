<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\OverviewStatisticsController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\QueryWithCompare;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * Automation analytics must reach the same tracked-only denominator the email
 * screens use, or a merchant sees two different open rates for one email and
 * trusts neither.
 */
class OverviewStatisticsControllerTest extends \MailPoetTest {
  /** @var OverviewStatisticsController */
  private $testee;

  public function _before() {
    parent::_before();
    $this->testee = $this->diContainer->get(OverviewStatisticsController::class);
  }

  public function testItReportsUntrackedAndTrackedSentAlongsideSent() {
    $newsletter = $this->createAutomationEmail(4);
    $this->createRecipients($newsletter, [true, true, true, false]);

    $data = $this->getStatistics($newsletter);

    verify($data['sent']['current'])->equals(4);
    verify($data['notTracked']['current'])->equals(1);
    verify($data['trackedSent']['current'])->equals(3);
    verify($data['opened']['current'])->equals(2);

    // 66.7%, not 50%.
    verify(round(($data['opened']['current'] * 100) / $data['trackedSent']['current'], 1))->equals(66.7);

    $email = array_values($data['emails'])[0];
    verify($email['sent']['current'])->equals(4);
    verify($email['notTracked'])->equals(1);
    verify($email['trackedSent'])->equals(3);
  }

  public function testTheAutomationTotalsSumTheUntrackedCountsAcrossEmails() {
    $first = $this->createAutomationEmail(4);
    $second = $this->createAutomationEmail(2);
    $this->createRecipients($first, [true, true, true, false]);
    $this->createRecipients($second, [true, false]);

    $data = $this->getStatistics($first, $second);

    verify($data['sent']['current'])->equals(6);
    verify($data['notTracked']['current'])->equals(2);
    verify($data['trackedSent']['current'])->equals(4);
  }

  public function testWithNoOptOutsTheTrackedFigureMatchesTheSentFigure() {
    $newsletter = $this->createAutomationEmail(3);
    $this->createRecipients($newsletter, [true, true, true]);

    $data = $this->getStatistics($newsletter);

    verify($data['notTracked']['current'])->equals(0);
    verify($data['trackedSent']['current'])->equals($data['sent']['current']);
    verify($data['trackedSent']['current'])->equals(3);
  }

  /**
   * The untracked figure must move with the window the same way sent does, or
   * subtracting one from the other stops meaning anything.
   */
  public function testTheUntrackedFigureRespectsTheQueryWindow() {
    $newsletter = $this->createAutomationEmail(2);
    $this->createRecipients($newsletter, [true, false]);

    $future = new \DateTimeImmutable('+10 days');
    $data = $this->getStatistics($newsletter, null, $future, $future->modify('+20 days'));

    verify($data['sent']['current'])->equals(0);
    verify($data['notTracked']['current'])->equals(0);
    verify($data['trackedSent']['current'])->equals(0);
  }

  private function createAutomationEmail(int $countProcessed): NewsletterEntity {
    return (new Newsletter())
      ->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withSendingQueue(['count_processed' => $countProcessed, 'count_total' => $countProcessed])
      ->create();
  }

  /**
   * @param bool[] $trackingAllowedFlags
   * @return SubscriberEntity[]
   */
  private function createRecipients(NewsletterEntity $newsletter, array $trackingAllowedFlags): array {
    $subscribers = [];
    foreach ($trackingAllowedFlags as $index => $trackingAllowed) {
      $subscriber = (new Subscriber())->create();
      (new StatisticsNewsletters($newsletter, $subscriber))
        ->withTrackingAllowed($trackingAllowed)
        ->create();
      // Two tracked recipients on the first email open it.
      if ($trackingAllowed && $index < 2) {
        (new StatisticsOpens($newsletter, $subscriber))->create();
      }
      $subscribers[] = $subscriber;
    }
    return $subscribers;
  }

  private function getStatistics(
    NewsletterEntity $newsletter,
    ?NewsletterEntity $second = null,
    ?\DateTimeImmutable $after = null,
    ?\DateTimeImmutable $before = null
  ): array {
    $steps = [
      new Step('trigger', Step::TYPE_TRIGGER, 'trigger', [], [new NextStep('email-1')]),
      new Step('email-1', Step::TYPE_ACTION, SendEmailAction::KEY, ['email_id' => $newsletter->getId()], $second ? [new NextStep('email-2')] : []),
    ];
    if ($second) {
      $steps[] = new Step('email-2', Step::TYPE_ACTION, SendEmailAction::KEY, ['email_id' => $second->getId()], []);
    }
    $automation = $this->tester->createAutomation('test', ...$steps);
    $this->assertNotNull($automation);

    $after = $after ?? new \DateTimeImmutable('-30 days');
    $before = $before ?? new \DateTimeImmutable('+1 day');
    $query = new QueryWithCompare(
      $after,
      $before,
      $after->modify('-60 days'),
      $after
    );

    return $this->testee->getStatisticsForAutomation($automation, $query);
  }
}
