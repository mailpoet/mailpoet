<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Settings\SettingsController;

require_once __DIR__ . '/../../../../lib/Migrations/App/Migration_20260515_120000_App.php';

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260515_120000_App_Test extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var Migration_20260515_120000_App */
  private $migration;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->migration = new Migration_20260515_120000_App($this->diContainer);
    $this->settings->delete('subscription');
    $this->settings->delete('db_version');
  }

  public function testItSetsClassicStyleForExistingInstallations(): void {
    $this->settings->set('db_version', '5.26.0');

    $this->migration->run();

    $this->assertSame(
      SettingsController::MANAGE_SUBSCRIPTION_PAGE_STYLE_CLASSIC,
      $this->settings->get('subscription.manage_subscription_page_style')
    );
  }

  public function testItSkipsNewInstallations(): void {
    $this->migration->run();

    $this->assertSame(
      SettingsController::MANAGE_SUBSCRIPTION_PAGE_STYLE_MODERN,
      $this->settings->get('subscription.manage_subscription_page_style')
    );
    $this->assertFalse($this->settings->hasSavedValue('subscription.manage_subscription_page_style'));
  }

  public function testItPreservesSavedStyle(): void {
    $this->settings->set('db_version', '5.26.0');
    $this->settings->set(
      'subscription.manage_subscription_page_style',
      SettingsController::MANAGE_SUBSCRIPTION_PAGE_STYLE_MODERN
    );

    $this->migration->run();

    $this->assertSame(
      SettingsController::MANAGE_SUBSCRIPTION_PAGE_STYLE_MODERN,
      $this->settings->get('subscription.manage_subscription_page_style')
    );
  }
}
