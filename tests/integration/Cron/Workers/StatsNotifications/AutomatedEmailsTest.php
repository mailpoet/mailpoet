<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use PHPUnit\Framework\MockObject\MockObject;
use MailPoet\WooCommerce\Helper as WCHelper;

class AutomatedEmailsTest extends \MailPoetTest {

  /** @var AutomatedEmails */
  private $stats_notifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ScheduledTask::createOrUpdate([
      'type' => AutomatedEmails::TASK_TYPE,
      'status' => null,
      'scheduled_at' => '2017-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = new SettingsController();
    $this->stats_notifications = $this->getMockBuilder(AutomatedEmails::class)
      ->enableOriginalConstructor()
      ->setConstructorArgs([
        $this->mailer,
        $this->renderer,
        $this->settings,
        $this->makeEmpty(WCHelper::class)
      ])
      ->setMethods(['getNewsletters'])
      ->getMock();
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => 'email@example.com',
    ]);
    $this->settings->set('tracking.enabled', true);
  }

  function testItDoesntWorkIfDisabled() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => false,
      'address' => 'email@example.com',
    ]);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesntWorkIfNoEmail() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => '',
    ]);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesntWorkIfTrackingIsDisabled() {
    $this->settings->set('tracking.enabled', false);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesntWorkIfEnabled() {
    expect($this->stats_notifications->checkProcessingRequirements())->equals(true);
  }

  function testItDoesntRenderIfNoNewslettersFound() {
    $this->stats_notifications
      ->expects($this->once())
      ->method('getNewsletters')
      ->will($this->returnValue([]));
    $this->renderer->expects($this->never())
      ->method('render');
    $this->mailer->expects($this->never())
      ->method('send');

    $result = $this->stats_notifications->process();

    expect($result)->equals(true);
  }

  function testItRenders() {
    $newsletter1 = Newsletter::create();
    $newsletter1->hydrate([
      'id' => 8765,
      'subject' => 'Subject',
      'total_sent' => 10,
      'statistics' => [
        'clicked' => 5,
        'opened' => 2,
      ],
    ]);
    $this->stats_notifications
      ->expects($this->once())
      ->method('getNewsletters')
      ->will($this->returnValue([$newsletter1]));
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

    $result = $this->stats_notifications->process();

    expect($result)->equals(true);
  }

  function testItSends() {
    $newsletter1 = Newsletter::create();
    $newsletter1->hydrate([
      'id' => 8765,
      'subject' => 'Subject',
      'total_sent' => 10,
      'statistics' => [
        'clicked' => 5,
        'opened' => 2,
      ],
    ]);
    $this->stats_notifications
      ->expects($this->once())
      ->method('getNewsletters')
      ->will($this->returnValue([$newsletter1]));

    $this->renderer->expects($this->exactly(2))
      ->method('render');

    $this->mailer->expects($this->once())
      ->method('send')
      ->with(
        $this->callback(function($rendered_newsletter){
          return ($rendered_newsletter['subject'] === 'Your monthly stats are in!')
            && isset($rendered_newsletter['body']);
        }),
        $this->equalTo('email@example.com')
      );

    $result = $this->stats_notifications->process();

    expect($result)->equals(true);
  }

  function testItPreparesContext() {
    $newsletter1 = Newsletter::create();
    $newsletter1->hydrate([
      'id' => 8765,
      'subject' => 'Subject',
      'total_sent' => 10,
      'statistics' => [
        'clicked' => 5,
        'opened' => 2,
      ],
    ]);
    $this->stats_notifications
      ->expects($this->once())
      ->method('getNewsletters')
      ->will($this->returnValue([$newsletter1]));
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return strpos($context['linkSettings'], 'mailpoet-settings');
        }));

    $this->stats_notifications->process();
  }

  function testItAddsNewsletterStatsToContext() {
    $newsletter1 = Newsletter::create();
    $newsletter1->hydrate([
      'id' => 8765,
      'subject' => 'Subject',
      'total_sent' => 10,
      'statistics' => [
        'clicked' => 5,
        'opened' => 2,
      ],
    ]);
    $this->stats_notifications
      ->expects($this->once())
      ->method('getNewsletters')
      ->will($this->returnValue([$newsletter1]));
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

    $this->stats_notifications->process();
  }

}
