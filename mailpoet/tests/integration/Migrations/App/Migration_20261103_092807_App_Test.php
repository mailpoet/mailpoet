<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoetVendor\Carbon\Carbon;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20261103_092807_App_Test extends \MailPoetTest {
  /** @var Migration_20261103_092807_App */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20261103_092807_App($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
  }

  public function _after() {
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    parent::_after();
  }

  public function testItMovesLaterDeadlineToOneMonthFromNow() {
    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, Carbon::now()->addMonths(11)->getTimestamp());

    $this->migration->run();

    $acceptedUntil = (int)$this->settings->get(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    verify($acceptedUntil)->greaterThan(Carbon::now()->addMonth()->subMinute()->getTimestamp());
    verify($acceptedUntil)->lessThanOrEqual(Carbon::now()->addMonth()->getTimestamp());
  }

  public function testItKeepsEarlierDeadline() {
    $acceptedUntil = Carbon::now()->addWeeks(2)->getTimestamp();
    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, $acceptedUntil);

    $this->migration->run();

    verify((int)$this->settings->get(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING))->equals($acceptedUntil);
  }

  public function testItDoesNotStartGracePeriod() {
    $this->migration->run();

    verify($this->settings->hasSavedValue(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING))->false();
  }
}
