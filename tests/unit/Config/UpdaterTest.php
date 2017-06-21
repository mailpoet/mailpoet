<?php

use Codeception\Util\Stub;
use MailPoet\Config\Updater;

class UpdaterTest extends MailPoetTest {
  function _before() {
    $this->plugin_name = 'some-plugin/some-plugin.php';
    $this->slug = 'some-plugin';
    $this->version = '0.1';

    $this->updater = new Updater(
      $this->plugin_name,
      $this->slug,
      $this->version
    );
  }

  function testItInitializes() {
    $updater = Stub::make(
      $this->updater,
      array(
        'checkForUpdate' => Stub::once()
      ),
      $this
    );
    $updater->init();
    apply_filters('pre_set_site_transient_update_plugins', null);
  }

  function testItChecksForUpdates() {
    $update_transient = new \StdClass;
    $update_transient->last_checked = time();
    $updater = Stub::construct(
      $this->updater,
      array(
        $this->plugin_name,
        $this->slug,
        $this->version
      ),
      array(
        'getLatestVersion' => function () {
          return (object)array(
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->plugin_name,
            'new_version' => $this->version . 1,
            'url' => 'http://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip'
          );
        }
      ),
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

  function testItReturnsObjectIfPassedNonObjectWhenCheckingForUpdates() {
    $result = $this->updater->checkForUpdate(null);
    expect($result instanceof \StdClass)->true();
  }
}
