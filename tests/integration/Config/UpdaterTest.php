<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Updater;

class UpdaterTest extends \MailPoetTest {
  public $updater;
  public $version;
  public $slug;
  public $plugin_name;
  public function _before() {
    parent::_before();
    $this->plugin_name = 'some-plugin/some-plugin.php';
    $this->slug = 'some-plugin';
    $this->version = '0.1';

    $this->updater = new Updater(
      $this->plugin_name,
      $this->slug,
      $this->version
    );
  }

  public function testItInitializes() {
    $updater = Stub::make(
      $this->updater,
      [
        'checkForUpdate' => Expected::once(),
      ],
      $this
    );
    $updater->init();
    apply_filters('pre_set_site_transient_update_plugins', null);
  }

  public function testItChecksForUpdates() {
    $update_transient = new \stdClass;
    $update_transient->last_checked = time();
    $updater = Stub::construct(
      $this->updater,
      [
        $this->plugin_name,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->plugin_name,
            'new_version' => $this->version . 1,
            'url' => 'http://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $result = $updater->checkForUpdate($update_transient);
    expect($result->last_checked)->greaterOrEquals($update_transient->last_checked);
    expect($result->checked[$this->plugin_name])->equals($this->version);
    expect($result->response[$this->plugin_name]->slug)->equals($this->slug);
    expect($result->response[$this->plugin_name]->plugin)->equals($this->plugin_name);
    expect(version_compare(
      $this->version,
      $result->response[$this->plugin_name]->new_version,
      '<'
    ))->true();
    expect($result->response[$this->plugin_name]->package)->notEmpty();
  }

  public function testItReturnsObjectIfPassedNonObjectWhenCheckingForUpdates() {
    $result = $this->updater->checkForUpdate(null);
    expect($result instanceof \stdClass)->true();
  }
}
