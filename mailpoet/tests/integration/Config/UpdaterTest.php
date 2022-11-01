<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Updater;

class UpdaterTest extends \MailPoetTest {
  /** @var Updater */
  public $updater;
  /** @var string */
  public $version;
  /** @var string */
  public $slug;
  /** @var string */
  public $pluginName;

  public function _before() {
    parent::_before();
    $this->pluginName = 'some-plugin/some-plugin.php';
    $this->slug = 'some-plugin';
    $this->version = '0.1';

    $this->updater = new Updater(
      $this->pluginName,
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
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => $this->version . 1,
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    expect($result->last_checked)->greaterOrEquals($updateTransient->last_checked); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect($result->checked[$this->pluginName])->equals($this->version);
    expect($result->response[$this->pluginName]->slug)->equals($this->slug);
    expect($result->response[$this->pluginName]->plugin)->equals($this->pluginName);
    expect(version_compare(
      $this->version,
      $result->response[$this->pluginName]->new_version,
      '<'
    ))->true();
    expect($result->response[$this->pluginName]->package)->notEmpty();
  }

  public function testItSetsNoupdateKeyIfNoUpdateAvailable() {
    $updateTransient = new \stdClass;
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $updater = Stub::construct(
      $this->updater,
      [
        $this->pluginName,
        $this->slug,
        $this->version,
      ],
      [
        'getLatestVersion' => function () {
          return (object)[
            'id' => 76630,
            'slug' => $this->slug,
            'plugin' => $this->pluginName,
            'new_version' => $this->version,
            'url' => 'https://www.mailpoet.com/wordpress-newsletter-plugin-premium/',
            'package' => home_url() . '/wp-content/uploads/mailpoet-premium.zip',
          ];
        },
      ],
      $this
    );
    $result = $updater->checkForUpdate($updateTransient);
    expect($result->last_checked)->greaterOrEquals($updateTransient->last_checked); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect($result->checked[$this->pluginName])->equals($this->version);
    expect($result->no_update[$this->pluginName]->slug)->equals($this->slug); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect($result->no_update[$this->pluginName]->plugin)->equals($this->pluginName); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(version_compare(
      $this->version,
      $result->no_update[$this->pluginName]->new_version, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      '='
    ))->true();
  }

  public function testItReturnsObjectIfPassedNonObjectWhenCheckingForUpdates() {
    $result = $this->updater->checkForUpdate(null);
    expect($result instanceof \stdClass)->true();
  }
}
