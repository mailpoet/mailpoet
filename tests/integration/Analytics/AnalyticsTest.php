<?php

namespace MailPoet\Analytics;

use Carbon\Carbon;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class AnalyticsTest extends \MailPoetTest {

  protected $backupGlobals = false;

  /** @var Analytics */
  private $analytics;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
    $this->analytics = new Analytics(
      new Reporter($this->settings, new WooCommerceHelper(new WPFunctions)),
      $this->settings
    );
    // Remove premium plugin hooks so that tests pass also with premium active
    remove_all_filters(Analytics::ANALYTICS_FILTER);
  }

  function testIsEnabledReturnsTrueIfSettingEnabled() {
    $this->settings->set('analytics', array('enabled' => '1'));
    expect($this->analytics->isEnabled())->true();
  }

  function testIsEnabledReturnsFalseIfEmptySettings() {
    $this->settings->set('analytics', array());
    expect($this->analytics->isEnabled())->false();
  }

  function testIsEnabledReturnsFalseIfNotEnabled() {
    $this->settings->set('analytics', array('enabled' => ''));
    expect($this->analytics->isEnabled())->false();
  }

  function testGetDataIfSettingsIsDisabled() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Expected::never(),
      ),
      $this
    );
    $this->settings->set('analytics', array('enabled' => ''));
    $analytics = new Analytics($reporter, new SettingsController());

    expect($analytics->generateAnalytics())->null();
  }

  function testGetDataIfSentRecently() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Expected::never(),
      ),
      $this
    );
    $this->settings->set('analytics', array('enabled' => '1'));
    $this->settings->set('analytics_last_sent', Carbon::now()->subHours(1));
    $analytics = new Analytics($reporter, new SettingsController());

    expect($analytics->generateAnalytics())->null();
  }

  function testGetDataIfEnabledButNeverSent() {
    $data = array();
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Expected::once(function() use ($data) {
          return $data;
        }),
      ),
      $this
    );
    $this->settings->set('analytics', array('enabled' => '1'));
    $this->settings->set('analytics_last_sent', null);

    $analytics = new Analytics($reporter, new SettingsController());
    expect($analytics->generateAnalytics())->equals(apply_filters(Analytics::ANALYTICS_FILTER, $data));
  }

  function testGetDataIfEnabledAndSentLongTimeAgo() {
    $data = array();
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Expected::once(function() use ($data){
          return $data;
        }),
      ),
      $this
    );
    $this->settings->set('analytics', array('enabled' => '1'));
    $this->settings->set('analytics_last_sent', Carbon::now()->subYear());

    $analytics = new Analytics($reporter, new SettingsController());

    expect($analytics->generateAnalytics())->equals(apply_filters(Analytics::ANALYTICS_FILTER, $data));
  }

  function testSetPublicId() {
    $fakePublicId = 'alk-ded-egrg-zaz-fvf-rtr-zdef';

    $this->settings->set('public_id', 'old-fake-public-id');
    $this->settings->set(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::now());

    $this->analytics->setPublicId($fakePublicId);

    expect($this->settings->get('public_id'))->equals($fakePublicId);
    expect($this->settings->get('new_public_id'))->equals('true');
    expect($this->settings->get(Analytics::SETTINGS_LAST_SENT_KEY, null))->null();
  }

  function testIsPublicIdNew() {
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
