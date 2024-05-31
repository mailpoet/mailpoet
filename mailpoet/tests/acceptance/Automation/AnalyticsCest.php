<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class AnalyticsCest {
  /** @var Automation */
  private $automation;

  /** @var array<string|int, Step> */
  private $automationStepSequence = [];

  /** @var NewsletterEntity */
  private $newsletter1;

  /** @var NewsletterEntity */
  private $newsletter2;

  public function _before() {
    $this->newsletter1 = $this->createNewsletter("Email 1");
    $this->newsletter2 = $this->createNewsletter("Email 2");
    $createdAt = (new \DateTimeImmutable())->modify('-2 years');
    $factory = (new DataFactories\Automation())
      ->withName('Someone Subscribed Automation')
      ->withSomeoneSubscribesTrigger()
      ->withSendEmailStep($this->newsletter1)
      ->withDelayAction()
      ->withSendEmailStep($this->newsletter2)
      ->withStatusActive()
      ->withCreatedAt($createdAt);

    $this->automation = $factory->create();
    $this->automationStepSequence = $factory->getStepSequence();

    // We need to alter the created_at date of the versions, so we can do historical comparisons.
    global $wpdb;
    $sql = 'update ' . $wpdb->prefix . 'mailpoet_automation_versions set created_at = %s where automation_id=%d';
    $wpdb->query($wpdb->prepare($sql, $createdAt->format(\DateTimeImmutable::W3C), $this->automation->getId()));
  }

  public function testOverviewOpens(\AcceptanceTester $i) {

    $i->wantTo('I want to check the overview opens and clicks');
    $i->login();

    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-automation-analytics&id=" . $this->automation->getId() . "&tab=automation-emails");
    $i->see('Overview');

    $i->waitForElement('.woocommerce-summary:not(.is-placeholder)');
    $i->see('Opened', '.woocommerce-summary > .woocommerce-summary__item-container');
    $i->see('0%', '.woocommerce-summary > .woocommerce-summary__item-container');
    $i->see('Clicked', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n)');
    $i->see('0%', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n)');

    $firstEmailSentCell = 'tbody > tr:nth-child(2n) > td:nth-child(2n) ';
    $firstEmailOpenCell = 'tbody > tr:nth-child(2n) > td:nth-child(3n) ';
    $firstEmailClickCell = 'tbody > tr:nth-child(2n) > td:nth-child(4n) ';
    $secondEmailSentCell = 'tbody > tr:nth-child(3n) > td:nth-child(2n) ';
    $secondEmailOpenCell = 'tbody > tr:nth-child(3n) > td:nth-child(3n) ';
    $secondEmailClickCell = 'tbody > tr:nth-child(3n) > td:nth-child(4n) ';

    $i->see('100', $firstEmailSentCell . '.mailpoet-analytics-main-value');
    $i->see('900%', $firstEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('100', $secondEmailSentCell . '.mailpoet-analytics-main-value');
    $i->see('900%', $secondEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $firstEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $secondEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0', $firstEmailOpenCell . '.mailpoet-analytics-main-value');
    $i->see('0', $secondEmailOpenCell . '.mailpoet-analytics-main-value');
    $i->see('0%', $firstEmailOpenCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $secondEmailOpenCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0', $firstEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('0', $secondEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('0%', $firstEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $secondEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('200sent', '.woocommerce-table__summary');
    $i->see('0opened', '.woocommerce-table__summary');
    $i->see('0clicked', '.woocommerce-table__summary');

    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $this->createRunForSubscriber($subscriber1);
    $this->createRunForSubscriber($subscriber2);
    $open1 = $this->createOpenForNewsletter($this->newsletter1, $subscriber1);
    $open2 = $this->createOpenForNewsletter($this->newsletter1, $subscriber2);
    $open3 = $this->createOpenForNewsletter($this->newsletter2, $subscriber2);
    $click1 = $this->createClickForNewsletter($this->newsletter1, $subscriber1);
    $click2 = $this->createClickForNewsletter($this->newsletter1, $subscriber2);

    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-automation-analytics&id=" . $this->automation->getId() . "&tab=automation-emails");

    $i->waitForElement('.woocommerce-summary:not(.is-placeholder)');
    $i->see('Opened', '.woocommerce-summary > .woocommerce-summary__item-container');
    $i->see('1.5%', '.woocommerce-summary > .woocommerce-summary__item-container');
    $i->see('Clicked', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n)');
    $i->see('1%', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n)');


    $i->see('100', $firstEmailSentCell . '.mailpoet-analytics-main-value');
    $i->see('100', $secondEmailSentCell . '.mailpoet-analytics-main-value');
    $i->see('900%', $firstEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('900%', $secondEmailSentCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('2', $firstEmailOpenCell . '.mailpoet-analytics-main-value');
    $i->see('1', $secondEmailOpenCell . '.mailpoet-analytics-main-value');
    $i->see('2%', $firstEmailOpenCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('1%', $secondEmailOpenCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('2', $firstEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('0', $secondEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('2%', $firstEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $secondEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');

    $i->see('200sent', '.woocommerce-table__summary');
    $i->see('3opened', '.woocommerce-table__summary');
    $i->see('2clicked', '.woocommerce-table__summary');

    $i->wantTo("Compare historical data");
    $date = (new \DateTimeImmutable('first day of this month'))->modify('+15 days')->modify('-3 months'); // 3 months ago, mid-month
    $this->alterCreateDateForClick($click1, $date);
    $this->alterCreateDateForOpen($open2, $date);

    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-automation-analytics&id=" . $this->automation->getId() . "&tab=automation-emails");

    $i->waitForElement('.woocommerce-summary:not(.is-placeholder)');
    $i->see('Opened', '.woocommerce-summary > .woocommerce-summary__item-container:first-child');
    $i->see('1%', '.woocommerce-summary > .woocommerce-summary__item-container:first-child .woocommerce-summary__item-value');
    $i->see('-80%', '.woocommerce-summary > .woocommerce-summary__item-container:first-child .woocommerce-summary__item-delta');

    $i->see('Clicked', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n)');
    $i->see('0.5%', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n) .woocommerce-summary__item-value');
    $i->see('-90%', '.woocommerce-summary > .woocommerce-summary__item-container:nth-child(2n) .woocommerce-summary__item-delta');

    $i->see('1', $firstEmailOpenCell . '.mailpoet-analytics-main-value');
    $i->see('1%', $firstEmailOpenCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('1', $firstEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('0', $secondEmailClickCell . '.mailpoet-analytics-main-value');
    $i->see('1%', $firstEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');
    $i->see('0%', $secondEmailClickCell . '.mailpoet-automation-analytics-table-subvalue');

  }

  public function automationFlowView(\AcceptanceTester $i) {

    $i->wantTo('See how many people wait in the automation flow');
    $i->login();

    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-automation-analytics&id=" . $this->automation->getId());
    $i->see('Overview');
    $i->waitForText('Opened');
    $i->see('0%');
    $i->dontSee('An unknown error occurred.');

    //Two subscribers have finished the run
    $this->createRunForSubscriber($this->createSubscriber());
    $this->createRunForSubscriber($this->createSubscriber());

    $automationSteps = $this->automation->getSteps();
    $delayStep = null;
    $firstEmailStep = null;
    $secondEmailStep = null;
    foreach ($automationSteps as $step) {
      if ($step->getKey() === 'core:delay') {
        $delayStep = $step->getId();
      }
      if ($step->getKey() === 'mailpoet:send-email' && $step->getArgs()['email_id'] === $this->newsletter1->getId()) {
        $firstEmailStep = $step->getId();
      }
      if ($step->getKey() === 'mailpoet:send-email' && $step->getArgs()['email_id'] === $this->newsletter2->getId()) {
        $secondEmailStep = $step->getId();
      }
    }
    // 1 subscriber is waiting at core:delay
    $this->createRunForSubscriber($this->createSubscriber(), $delayStep, AutomationRun::STATUS_RUNNING);

    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-automation-analytics&id=" . $this->automation->getId());
    $i->waitForText('Opened');
    $i->see('3 entered');

    $i->scrollTo('.mailpoet-automation-analytics-separator-' . $firstEmailStep);
    $i->see('0% (0) waiting', '#step-' . $firstEmailStep);
    $i->see('100% (3) completed', '.mailpoet-automation-analytics-separator-' . $firstEmailStep);

    $i->scrollTo('.mailpoet-automation-analytics-separator-' . $delayStep);
    $i->see('33% (1) waiting', '#step-' . $delayStep);
    $i->see('67% (2) completed', '.mailpoet-automation-analytics-separator-' . $delayStep);

    $i->scrollTo('.mailpoet-automation-editor-automation-end');
    $i->see('0% (0) waiting', '#step-' . $secondEmailStep);
    $i->see('67% (2) completed', '.mailpoet-automation-analytics-separator-' . $secondEmailStep);

    $i->wantTo("See that in the free version I see the sample data text");
    $i->scrollToTop();
    $i->click('button.mailpoet-analytics-tab-subscribers');
    $i->see("You're previewing a report with sample data.");
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = (new DataFactories\Subscriber())
      ->create();

    return $subscriber;
  }

  private function createRunForSubscriber(SubscriberEntity $subscriber, string $nextStep = null, string $status = AutomationRun::STATUS_COMPLETE) {
    $run = (new DataFactories\AutomationRun())
      ->withAutomation($this->automation)
      ->withStatus($nextStep ? $status : AutomationRun::STATUS_COMPLETE)
      ->withNextStep($nextStep)
      ->withSubject(new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriber->getId()]))
      ->create();

    foreach ($this->automationStepSequence as $step) {
      if ($step->getId() === $nextStep) {
        break;
      }
      (new DataFactories\AutomationRunLog($run->getId(), $step))->create();
    }
  }

  private function createNewsletter($newsletterTitle) {

    $date = (new \DateTimeImmutable('first day of this month'))->modify('+15 days')->modify('-3 months'); // 3 months ago, mid-month
    return (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->withSentStatus()
      ->withActiveStatus()
      ->withSendingQueue([
        'count_processed' => 100,
        'count_total' => 100,
      ])
      ->withSendingQueue([
        'count_processed' => 10,
        'count_total' => 10,
        'created_at' => $date,
        'updated_at' => $date,
      ])
      ->create();
  }

  private function createClickForNewsletter(NewsletterEntity $email, SubscriberEntity $subscriber): StatisticsClickEntity {
    $link = (new DataFactories\NewsletterLink($email))->create();
    return (new DataFactories\StatisticsClicks($link, $subscriber))->create();
  }

  private function alterCreateDateForClick(StatisticsClickEntity $click, \DateTimeImmutable $created) {
    $em = ContainerWrapper::getInstance()->get(EntityManager::class);

    $em->createQueryBuilder()->update(StatisticsClickEntity::class, 'c')
      ->set('c.createdAt', ':createdAt')
      ->set('c.updatedAt', ':updatedAt')
      ->where('c.id = :id')
      ->setParameter('createdAt', $created)
      ->setParameter('updatedAt', $created)
      ->setParameter('id', $click->getId())
      ->getQuery()->execute();
  }

  private function alterCreateDateForOpen(StatisticsOpenEntity $open, \DateTimeImmutable $created) {
    $em = ContainerWrapper::getInstance()->get(EntityManager::class);

    $em->createQueryBuilder()->update(StatisticsOpenEntity::class, 'c')
      ->set('c.createdAt', ':createdAt')
      ->where('c.id = :id')
      ->setParameter('createdAt', $created)
      ->setParameter('id', $open->getId())
      ->getQuery()->execute();
  }

  private function createOpenForNewsletter(NewsletterEntity $email, SubscriberEntity $subscriber): StatisticsOpenEntity {
    return (new DataFactories\StatisticsOpens($email, $subscriber))->create();
  }
}
