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
    $installer = Stub::construct(
      $this->installer,
      array(
        $this->slug
      ),
      array(
        'retrievePluginInformation' => function () {
          $obj = new \stdClass();
          $obj->slug = $this->slug;
          $obj->plugin_name = 'MailPoet Premium';
          $obj->new_version = '3.0.0-alpha.0.0.3.1';
          $obj->requires = '4.6';
          $obj->tested = '4.7.4';
          $obj->downloaded = 12540;
          $obj->last_updated = date('Y-m-d');
          $obj->sections = array(
            'description' => 'The new version of the Premium plugin',
            'another_section' => 'This is another section',
            'changelog' => 'Some new features'
          );
          $obj->download_link = home_url() . '/wp-content/uploads/mailpoet-premium.zip';
          $obj->package = $obj->download_link;
          return $obj;
        }
      )
    );
    $result = $installer->getPluginInfo(false, 'plugin_information', $args);
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
