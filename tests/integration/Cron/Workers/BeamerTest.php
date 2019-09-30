<?php

namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use Codeception\Stub;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class BeamerTest extends \MailPoetTest {

  function testItSetsLastAnnouncementDate() {
    $oldDate = '2019-05-18T10:25:00.000Z';
    $newDate = '2019-05-22T10:25:00.000Z';
    $settings = new SettingsController;
    $settings->set('last_announcement_date', Carbon::createFromTimeString($oldDate)->getTimestamp());
    $wp = Stub::make(new WPFunctions, [
      'wpRemoteGet' => null,
      'wpRemoteRetrieveBody' => json_encode([
        ['date' => $newDate],
      ]),
    ]);
    $beamer = new Beamer($settings, $wp);
    $done = $beamer->setLastAnnouncementDate();
    expect($done)->equals(true);
    expect($settings->get('last_announcement_date'))->equals(Carbon::createFromTimeString($newDate)->getTimestamp());
  }

  function testItDoesNothingIfNoResponse() {
    $oldDate = '2019-05-18T10:25:00.000Z';
    $settings = new SettingsController;
    $settings->set('last_announcement_date', Carbon::createFromTimeString($oldDate)->getTimestamp());
    $wp = Stub::make(new WPFunctions, [
      'wpRemoteGet' => null,
      'wpRemoteRetrieveBody' => null,
    ]);
    $beamer = new Beamer($settings, $wp);
    $done = $beamer->setLastAnnouncementDate();
    expect($done)->equals(false);
    expect($settings->get('last_announcement_date'))->equals(Carbon::createFromTimeString($oldDate)->getTimestamp());
  }

  function testItDoesNothingIfWrongResponse() {
    $oldDate = '2019-05-18T10:25:00.000Z';
    $settings = new SettingsController;
    $settings->set('last_announcement_date', Carbon::createFromTimeString($oldDate)->getTimestamp());
    $wp = Stub::make(new WPFunctions, [
      'wpRemoteGet' => null,
      'wpRemoteRetrieveBody' => '[{"corrupted":"json data',
    ]);
    $beamer = new Beamer($settings, $wp);
    $done = $beamer->setLastAnnouncementDate();
    expect($done)->equals(false);
    expect($settings->get('last_announcement_date'))->equals(Carbon::createFromTimeString($oldDate)->getTimestamp());
  }

  function testItDoesNothingIfEmptyList() {
    $oldDate = '2019-05-18T10:25:00.000Z';
    $settings = new SettingsController;
    $settings->set('last_announcement_date', Carbon::createFromTimeString($oldDate)->getTimestamp());
    $wp = Stub::make(new WPFunctions, [
      'wpRemoteGet' => null,
      'wpRemoteRetrieveBody' => '[]',
    ]);
    $beamer = new Beamer($settings, $wp);
    $done = $beamer->setLastAnnouncementDate();
    expect($done)->equals(false);
    expect($settings->get('last_announcement_date'))->equals(Carbon::createFromTimeString($oldDate)->getTimestamp());
  }

}
