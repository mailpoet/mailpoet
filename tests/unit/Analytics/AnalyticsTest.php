<?php

namespace MailPoet\Analytics;

use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\Models\Setting;

class AnalyticsTest extends \MailPoetTest {

  protected $backupGlobals = false;

  function testIsEnabledReturnsTrueIfSettingEnabled() {

    Setting::setValue('analytics', array('enabled' => '1'));

    $analytics = new Analytics(new Reporter());
    expect($analytics->isEnabled())->true();
  }

  function testIsEnabledReturnsFalseIfEmptySettings() {

    Setting::setValue('analytics', array());

    $analytics = new Analytics(new Reporter());
    expect($analytics->isEnabled())->false();
  }

  function testIsEnabledReturnsFalseIfNotEnabled() {

    Setting::setValue('analytics', array('enabled' => ''));

    $analytics = new Analytics(new Reporter());
    expect($analytics->isEnabled())->false();
  }

  function testGetDataIfSettingsIsDisabled() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Stub::never(),
      ),
      $this
    );
    Setting::setValue('analytics', array('enabled' => ''));
    $analytics = new Analytics($reporter);

    expect($analytics->generateAnalytics())->null();
  }

  function testGetDataIfSentRecently() {
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Stub::never(),
      ),
      $this
    );
    Setting::setValue('analytics', array('enabled' => '1'));
    Setting::setValue('analytics_last_sent', Carbon::now()->subHours(1));
    $analytics = new Analytics($reporter);

    expect($analytics->generateAnalytics())->null();
  }

  function testGetDataIfEnabledButNeverSent() {
    $data = array();
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Stub::once(function() use ($data){
          return $data;
        }),
      ),
      $this
    );
    Setting::setValue('analytics', array('enabled' => '1'));
    Setting::setValue('analytics_last_sent', null);

    $analytics = new Analytics($reporter);

    expect($analytics->generateAnalytics())->equals($data);
  }

  function testGetDataIfEnabledAndSentLongTimeAgo() {
    $data = array();
    $reporter = Stub::makeEmpty(
      'MailPoet\Analytics\Reporter',
      array(
        'getData' => Stub::once(function() use ($data){
          return $data;
        }),
      ),
      $this
    );
    Setting::setValue('analytics', array('enabled' => '1'));
    Setting::setValue('analytics_last_sent', Carbon::now()->subYear());

    $analytics = new Analytics($reporter);

    expect($analytics->generateAnalytics())->equals($data);
  }

}