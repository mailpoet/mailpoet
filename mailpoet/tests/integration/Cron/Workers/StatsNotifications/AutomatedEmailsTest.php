<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\StatsNotifications;

use Codeception\Stub;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\StatisticsClicks as StatisticsClicksFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class AutomatedEmailsTest extends \MailPoetTest {

  /** @var AutomatedEmails */
  private $statsNotifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  public function _before() {
    parent::_before();

    $scheduledTaskFactory = new ScheduledTaskFactory();
    $scheduledTaskFactory->create(
      AutomatedEmails::TASK_TYPE,
      null,
      new Carbon('2017-01-02 12:13:14')
    );

    $this->mailer = $this->createMock(Mailer::class);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')
      ->willReturn($this->mailer);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->statsNotifications = new AutomatedEmails(
      $mailerFactory,
      $this->renderer,
      $this->settings,
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      new MetaInfo,
      $this->diContainer->get(TrackingConfig::class)
    );
    $this->cronWorkerRunner = Stub::copy($this->diContainer->get(CronWorkerRunner::class), [
      'timer' => microtime(true), // reset timer to avoid timeout during full test suite run
    ]);

    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => 'email@example.com',
    ]);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);

    $this->newsletterFactory = new NewsletterFactory();
  }

  public function testItDoesntWorkIfDisabled() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => false,
      'address' => 'email@example.com',
    ]);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesntWorkIfNoEmail() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => '',
    ]);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesntWorkIfTrackingIsDisabled() {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesWorkIfEnabled() {
    expect($this->statsNotifications->checkProcessingRequirements())->equals(true);
  }

  public function testItDoesntRenderIfNoNewslettersFound() {
    $this->renderer->expects($this->never())
      ->method('render');
    $this->mailer->expects($this->never())
      ->method('send');

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItRenders() {
    $this->createNewsletterClicksAndOpens();
    $this->renderer->expects($this->exactly(2))
      ->method('render');
    $this->renderer->expects($this->at(0))
      ->method('render')
      ->with($this->equalTo('emails/statsNotificationAutomatedEmails.html'));

    $this->renderer->expects($this->at(1))
      ->method('render')
      ->with($this->equalTo('emails/statsNotificationAutomatedEmails.txt'));

    $this->mailer->expects($this->once())
      ->method('send');

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItSends() {
    $this->createNewsletterClicksAndOpens();

    $this->renderer->expects($this->exactly(2))
      ->method('render');

    $this->mailer->expects($this->once())
      ->method('send')
      ->with(
        $this->callback(function($renderedNewsletter){
          return ($renderedNewsletter['subject'] === 'Your monthly stats are in!')
            && isset($renderedNewsletter['body']);
        }),
        $this->equalTo('email@example.com')
      );

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItPreparesContext() {
    $this->createNewsletterClicksAndOpens();
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context): bool {
          return (bool)strpos($context['linkSettings'], 'mailpoet-settings');
        }));

    $this->cronWorkerRunner->run($this->statsNotifications);
  }

  public function testItAddsNewsletterStatsToContext() {
    $this->createNewsletterClicksAndOpens();

    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return strpos($context['newsletters'][0]['linkStats'], 'page=mailpoet-newsletters#/stats')
            && $context['newsletters'][0]['clicked'] === 50
            && $context['newsletters'][0]['opened'] === 20
            && $context['newsletters'][0]['subject'] === 'Subject';
        }));

    $this->cronWorkerRunner->run($this->statsNotifications);
  }

  private function createClicks(NewsletterEntity $newsletter, int $count) {
    $newsletterLink = (new NewsletterLinkFactory($newsletter))->create();

    for ($i = 0; $i < $count; $i++) {
      $subscriber = (new SubscriberFactory())->create();
      (new StatisticsClicksFactory($newsletterLink, $subscriber))->create();
    }
  }

  private function createOpens(NewsletterEntity $newsletter, $count) {
    for ($i = 0; $i < $count; $i++) {
      $subscriber = (new SubscriberFactory())->create();
      (new StatisticsOpensFactory($newsletter, $subscriber))->create();
    }
  }

  private function createNewsletterClicksAndOpens() {
    $newsletter = $this->newsletterFactory
      ->withSubject('Subject')
      ->withWelcomeTypeForSegment(1)
      ->withActiveStatus()
      ->withSendingQueue(['count_processed' => 10])
      ->create();

    $this->createClicks($newsletter, 5);
    $this->createOpens($newsletter, 2);
  }
}
