<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\TrackingConsentController;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260805_120000_App_Test extends \MailPoetTest {
  /** @var Migration_20260805_120000_App */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260805_120000_App($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE);
  }

  public function testItMovesStrictSitesToAskingEveryone() {
    $this->settings->set('db_version', '5.35.0');
    $this->settings->set(Migration_20260805_120000_App::LEGACY_TRACK_UNKNOWN, false);

    $this->migration->run();

    $this->assertEquals(
      TrackingConsentController::CHOICE_ASK_ALL,
      $this->settings->get(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE)
    );
  }

  public function testItMovesEveryOtherExistingSiteToTrackingEveryone() {
    $this->settings->set('db_version', '5.35.0');
    $this->settings->set(Migration_20260805_120000_App::LEGACY_TRACK_UNKNOWN, true);

    $this->migration->run();

    $this->assertEquals(
      TrackingConsentController::CHOICE_TRACK_ALL,
      $this->settings->get(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE)
    );
  }

  public function testItTreatsAnExistingSiteThatNeverSetTheLegacyValueAsTrackingEveryone() {
    $this->settings->set('db_version', '5.35.0');
    $this->settings->delete(Migration_20260805_120000_App::LEGACY_TRACK_UNKNOWN);

    $this->migration->run();

    $this->assertEquals(
      TrackingConsentController::CHOICE_TRACK_ALL,
      $this->settings->get(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE)
    );
  }

  public function testItLeavesFreshInstallsAlone() {
    $this->settings->delete('db_version');
    // A value the migration would overwrite if it ran, so the assertion below
    // proves the guard rather than coinciding with the default.
    $this->settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);

    $this->migration->run();

    $this->assertEquals(
      TrackingConsentController::CHOICE_ASK_ALL,
      $this->settings->get(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE)
    );
  }
}
