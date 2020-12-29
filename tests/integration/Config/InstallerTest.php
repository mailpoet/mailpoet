<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Env;
use MailPoet\Config\Installer;
use MailPoet\WP\Functions as WPFunctions;

class InstallerTest extends \MailPoetTest {
  public $installer;
  public $slug;

  public function _before() {
    parent::_before();
    $this->slug = 'some-plugin';

    $this->installer = new Installer(
      $this->slug
    );
  }

  public function testItInitializes() {
    $installer = Stub::make(
      $this->installer,
      [
        'getPluginInformation' => Expected::once(),
      ],
      $this
    );
    $installer->init();
    WPFunctions::get()->applyFilters('plugins_api', null, null, null);
  }

  public function testItGetsPluginInformation() {
    $args = new \stdClass;
    $args->slug = $this->slug;
    $installer = Stub::construct(
      $this->installer,
      [
        $this->slug,
      ],
      [
        'retrievePluginInformation' => function () {
          $obj = new \stdClass();
          $obj->slug = $this->slug;
          $obj->name = 'MailPoet Premium';
          $obj->new_version = '3.0.0-alpha.0.0.3.1'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          $obj->requires = '4.6';
          $obj->tested = '4.7.4';
          $obj->downloaded = 12540;
          $obj->last_updated = date('Y-m-d'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          $obj->sections = [
            'description' => 'The new version of the Premium plugin',
            'another_section' => 'This is another section',
            'changelog' => 'Some new features',
          ];
          $obj->download_link = home_url() . '/wp-content/uploads/mailpoet-premium.zip'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          $obj->package = $obj->download_link; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          return $obj;
        },
      ],
      $this
    );
    $result = $installer->getPluginInformation(false, 'plugin_information', $args);
    expect($result->slug)->equals($this->slug);
    expect($result->new_version)->notEmpty(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($result->download_link)->notEmpty(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($result->package)->notEmpty();
  }

  public function testItIgnoresNonMatchingRequestsWhenGettingPluginInformation() {
    $data = new \stdClass;
    $data->someProperty = '123';
    $result = $this->installer->getPluginInformation($data, 'some_action', null);
    expect($result)->equals($data);
    $args = new \stdClass;
    $args->slug = 'different-slug';
    $result = $this->installer->getPluginInformation($data, 'plugin_information', $args);
    expect($result)->equals($data);
  }

  public function testItGetsPremiumStatus() {
    $status = Installer::getPremiumStatus();
    expect(isset($status['premium_plugin_active']))->true();
    expect(isset($status['premium_plugin_installed']))->true();
  }

  public function testItChecksIfAPluginIsInstalled() {
    expect(Installer::isPluginInstalled(Env::$pluginName))->true();
    expect(Installer::isPluginInstalled('some-non-existent-plugin-123'))->false();
  }
}
