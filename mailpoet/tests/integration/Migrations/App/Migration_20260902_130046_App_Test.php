<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260902_130046_App_Test extends \MailPoetTest {
  /** @var Migration_20260902_130046_App */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260902_130046_App($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('db_version', '5.37.0');
  }

  public function testItMovesBeijingToNingxia() {
    $this->setMailerConfig(['method' => Mailer::METHOD_AMAZONSES, 'region' => 'cn-north-1', 'access_key' => 'key']);

    $this->migration->run();

    $config = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $this->assertEquals('cn-northwest-1', $config['region']);
    $this->assertEquals('key', $config['access_key']);
  }

  public function testItLeavesEveryOtherRegionAlone() {
    $this->setMailerConfig(['method' => Mailer::METHOD_AMAZONSES, 'region' => 'eu-west-1']);

    $this->migration->run();

    $config = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $this->assertEquals('eu-west-1', $config['region']);
  }

  public function testItLeavesOtherSendingMethodsAlone() {
    $this->setMailerConfig(['method' => Mailer::METHOD_SMTP, 'region' => 'cn-north-1']);

    $this->migration->run();

    $config = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $this->assertEquals('cn-north-1', $config['region']);
  }

  public function testItSkipsNewInstalls() {
    $this->settings->delete('db_version');
    $this->setMailerConfig(['method' => Mailer::METHOD_AMAZONSES, 'region' => 'cn-north-1']);

    $this->migration->run();

    $config = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    $this->assertEquals('cn-north-1', $config['region']);
  }

  private function setMailerConfig(array $config): void {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $config);
  }
}
