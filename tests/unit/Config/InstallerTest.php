<?php

use Codeception\Util\Stub;
use MailPoet\Config\Env;
use MailPoet\Config\Installer;

class InstallerTest extends MailPoetTest {
  function _before() {
    $this->slug = 'some-plugin';

    $this->installer = new Installer(
      $this->slug
    );
  }

  function testItInitializes() {
    $installer = Stub::make(
      $this->installer,
      array(
        'getPluginInfo' => Stub::once()
      )
    );
    $installer->init();
    apply_filters('plugins_api', null, null, null);
  }

  function testItGetsPluginInfo() {
    $args = new \StdClass;
    $args->slug = $this->slug;
    $result = $this->installer->getPluginInfo(false, 'plugin_information', $args);
    expect($result->slug)->equals($this->slug);
    expect($result->new_version)->notEmpty();
    expect($result->download_link)->notEmpty();
    expect($result->package)->notEmpty();
  }

  function testItIgnoresNonMatchingRequestsWhenGettingPluginInfo() {
    $data = new \StdClass;
    $data->some_property = '123';
    $result = $this->installer->getPluginInfo($data, 'some_action', null);
    expect($result)->equals($data);
    $args = new \StdClass;
    $args->slug = 'different-slug';
    $result = $this->installer->getPluginInfo($data, 'plugin_information', $args);
    expect($result)->equals($data);
  }

  function testItGetsPremiumStatus() {
    $status = Installer::getPremiumStatus();
    expect(isset($status['premium_plugin_active']))->true();
    expect(isset($status['premium_plugin_installed']))->true();
    expect(isset($status['premium_install_url']))->true();
    expect(isset($status['premium_activate_url']))->true();
  }

  function testItChecksIfAPluginIsInstalled() {
    expect(Installer::isPluginInstalled(Env::$plugin_name))->true();
    expect(Installer::isPluginInstalled('some-non-existent-plugin-123'))->false();
  }

  function testItGetsPluginInstallUrl() {
    expect(Installer::getPluginInstallUrl(Env::$plugin_name))
      ->startsWith(home_url() . '/wp-admin/update.php?action=install-plugin&plugin=mailpoet&_wpnonce=');
  }

  function testItGetsPluginActivateUrl() {
    expect(Installer::getPluginActivateUrl(Env::$plugin_name))
      ->startsWith(home_url() . '/wp-admin/plugins.php?action=activate&plugin=mailpoet/mailpoet.php&_wpnonce=');
  }
}
