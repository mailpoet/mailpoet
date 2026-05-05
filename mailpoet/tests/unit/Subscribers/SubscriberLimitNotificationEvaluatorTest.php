<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationEvaluator.php';
require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationMailer.php';

class SubscriberLimitNotificationEvaluatorTest extends \MailPoetUnitTest {
  public function testItSendsBothThresholdsInOrderAndPersistsAfterEachSend(): void {
    $state = [];
    $setValues = [];
    $settings = $this->createSettingsMock($state, $setValues);

    $feature = $this->createMock(SubscribersFeature::class);
    $feature->method('getFreeSubscriberLimitForNotifications')->willReturn(1000);
    $feature->method('getFreshSubscribersCount')->willReturn(990);

    $mailer = $this->createMock(SubscriberLimitNotificationMailer::class);
    $mailer->expects($this->exactly(2))
      ->method('send')
      ->withConsecutive([95, 990, 1000], [99, 990, 1000])
      ->willReturn(true);

    $evaluator = new SubscriberLimitNotificationEvaluator($settings, $feature, $mailer, $this->createWpMock());
    $evaluator->evaluate();

    verify($state['thresholds']['95']['count_at_send'])->equals(990);
    verify($state['thresholds']['99']['count_at_send'])->equals(990);
    verify($setValues)->arrayCount(2);
  }

  public function testItDoesNotSendDuplicateThresholdWhileCountStaysAboveIt(): void {
    $state = [
      'limit' => 1000,
      'thresholds' => [
        '95' => [
          'sent_at' => '2026-05-05 10:00:00',
          'count_at_send' => 950,
        ],
      ],
    ];
    $setValues = [];
    $settings = $this->createSettingsMock($state, $setValues);

    $feature = $this->createMock(SubscribersFeature::class);
    $feature->method('getFreeSubscriberLimitForNotifications')->willReturn(1000);
    $feature->method('getFreshSubscribersCount')->willReturn(960);

    $mailer = $this->createMock(SubscriberLimitNotificationMailer::class);
    $mailer->expects($this->never())->method('send');

    $evaluator = new SubscriberLimitNotificationEvaluator($settings, $feature, $mailer, $this->createWpMock());
    $evaluator->evaluate();

    verify($setValues)->arrayCount(0);
  }

  public function testItClearsThresholdWhenCountDropsBelowIt(): void {
    $state = [
      'limit' => 1000,
      'thresholds' => [
        '95' => [
          'sent_at' => '2026-05-05 10:00:00',
          'count_at_send' => 950,
        ],
        '99' => [
          'sent_at' => '2026-05-05 10:10:00',
          'count_at_send' => 990,
        ],
      ],
    ];
    $setValues = [];
    $settings = $this->createSettingsMock($state, $setValues);

    $feature = $this->createMock(SubscribersFeature::class);
    $feature->method('getFreeSubscriberLimitForNotifications')->willReturn(1000);
    $feature->method('getFreshSubscribersCount')->willReturn(960);

    $mailer = $this->createMock(SubscriberLimitNotificationMailer::class);
    $mailer->expects($this->never())->method('send');

    $evaluator = new SubscriberLimitNotificationEvaluator($settings, $feature, $mailer, $this->createWpMock());
    $evaluator->evaluate();

    verify(isset($state['thresholds']['95']))->true();
    verify(isset($state['thresholds']['99']))->false();
  }

  public function testItDoesNotMarkThresholdAfterFailedSend(): void {
    $state = [];
    $setValues = [];
    $settings = $this->createSettingsMock($state, $setValues);

    $feature = $this->createMock(SubscribersFeature::class);
    $feature->method('getFreeSubscriberLimitForNotifications')->willReturn(1000);
    $feature->method('getFreshSubscribersCount')->willReturn(950);

    $mailer = $this->createMock(SubscriberLimitNotificationMailer::class);
    $mailer->expects($this->once())
      ->method('send')
      ->with(95, 950, 1000)
      ->willReturn(false);

    $evaluator = new SubscriberLimitNotificationEvaluator($settings, $feature, $mailer, $this->createWpMock());
    $evaluator->evaluate();

    verify(isset($state['thresholds']['95']))->false();
  }

  public function testItClearsStateWhenSiteIsNotEligibleForFreeLimit(): void {
    $state = [
      'limit' => 1000,
      'thresholds' => [
        '95' => [
          'sent_at' => '2026-05-05 10:00:00',
          'count_at_send' => 950,
        ],
      ],
    ];
    $setValues = [];
    $settings = $this->createSettingsMock($state, $setValues);

    $feature = $this->createMock(SubscribersFeature::class);
    $feature->method('getFreeSubscriberLimitForNotifications')->willReturn(null);
    $feature->expects($this->never())->method('getFreshSubscribersCount');

    $mailer = $this->createMock(SubscriberLimitNotificationMailer::class);
    $mailer->expects($this->never())->method('send');

    $evaluator = new SubscriberLimitNotificationEvaluator($settings, $feature, $mailer, $this->createWpMock());
    $evaluator->evaluate();

    verify($state)->equals([]);
  }

  private function createSettingsMock(array &$state, array &$setValues): SettingsController {
    $settings = $this->createMock(SettingsController::class);
    $settings->expects($this->once())
      ->method('fetch')
      ->with(SubscriberLimitNotificationEvaluator::SETTINGS_KEY, [])
      ->willReturnCallback(function() use (&$state) {
        return $state;
      });
    $settings->method('get')
      ->willReturnCallback(function() use (&$state) {
        return $state;
      });
    $settings->method('set')
      ->willReturnCallback(function(string $key, $value) use (&$state, &$setValues): void {
        verify($key)->equals(SubscriberLimitNotificationEvaluator::SETTINGS_KEY);
        $state = $value;
        $setValues[] = $value;
      });
    return $settings;
  }

  private function createWpMock(): WPFunctions {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('currentTime')->with('mysql', true)->willReturn('2026-05-05 12:00:00');
    return $wp;
  }
}
