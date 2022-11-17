<?php declare(strict_types = 1);

namespace MailPoet\Analytics;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class AnalyticsTest extends \MailPoetTest {

  protected $backupGlobals = false;

  /** @var Analytics */
  private $analytics;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->analytics = $this->diContainer->get(Analytics::class);
    // Remove premium plugin hooks so that tests pass also with premium active
    remove_all_filters(Analytics::ANALYTICS_FILTER);
  }

  public function testIsEnabledReturnsTrueIfSettingEnabled() {
    $this->settings->set('analytics', ['enabled' => '1']);
    expect($this->analytics->isEnabled())->true();
  }

  public function testIsEnabledReturnsFalseIfEmptySettings() {
    $this->settings->set('analytics', []);
    expect($this->analytics->isEnabled())->false();
  }

  public function testIsEnabledReturnsFalseIfNotEnabled() {
    $this->settings->set('analytics', ['enabled' => '']);
    expect($this->analytics->isEnabled())->false();
  }

  public function testGetDataIfSettingsIsDisabled() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      [
        'getData' => Expected::never(),
      ],
      $this
    );
    $this->settings->set('analytics', ['enabled' => '']);
    $analytics = new Analytics($reporter, SettingsController::getInstance());

    expect($analytics->generateAnalytics())->null();
  }

  public function testGetDataIfSentRecently() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      [
        'getData' => Expected::never(),
      ],
      $this
    );
    $this->settings->set('analytics', ['enabled' => '1']);
    $this->settings->set('analytics_last_sent', Carbon::now()->subHours(1));
    $analytics = new Analytics($reporter, SettingsController::getInstance());

    expect($analytics->generateAnalytics())->null();
  }

  public function testGetDataIfEnabledButNeverSent() {
    $data = [];
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      [
        'getData' => Expected::once(function() use ($data) {
          return $data;
        }),
      ],
      $this
    );
    $this->settings->set('analytics', ['enabled' => '1']);
    $this->settings->set('analytics_last_sent', null);

    $analytics = new Analytics($reporter, SettingsController::getInstance());
    expect($analytics->generateAnalytics())->equals(apply_filters(Analytics::ANALYTICS_FILTER, $data));
  }

  public function testGetDataIfEnabledAndSentLongTimeAgo() {
    $data = [];
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      [
        'getData' => Expected::once(function() use ($data){
          return $data;
        }),
      ],
      $this
    );
    $this->settings->set('analytics', ['enabled' => '1']);
    $this->settings->set('analytics_last_sent', Carbon::now()->subYear());

    $analytics = new Analytics($reporter, SettingsController::getInstance());

    expect($analytics->generateAnalytics())->equals(apply_filters(Analytics::ANALYTICS_FILTER, $data));
  }

  public function testSetPublicId() {
    $fakePublicId = 'alk-ded-egrg-zaz-fvf-rtr-zdef';

    $this->settings->set('public_id', 'old-fake-public-id');
    $this->settings->set(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::now());

    $this->analytics->setPublicId($fakePublicId);

    expect($this->settings->get('public_id'))->equals($fakePublicId);
    expect($this->settings->get('new_public_id'))->equals('true');
    expect($this->settings->get(Analytics::SETTINGS_LAST_SENT_KEY, null))->null();
  }

  public function testIsPublicIdNew() {
    $fakePublicId = 'alk-ded-egrg-zaz-fvf-rtr-zdef';

    $this->settings->set('public_id', 'old-fake-public-id');
    $this->settings->set('new_public_id', 'false');

    $this->analytics->setPublicId($fakePublicId);
    // When we update public_id it's marked as new
    expect($this->settings->get('new_public_id'))->equals('true');
    expect($this->analytics->isPublicIdNew())->true();
    expect($this->settings->get('new_public_id'))->equals('false');

    $this->analytics->setPublicId($fakePublicId);
    // We tried to update public_id with the same value, so it's not marked as new
    expect($this->settings->get('new_public_id'))->equals('false');
    expect($this->analytics->isPublicIdNew())->false();
    expect($this->settings->get('new_public_id'))->equals('false');
  }
}
