<?php declare(strict_types=1);

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;

class PopulatorTest extends \MailPoetTest {

  /** @var Populator */
  private $populator;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->populator = $this->diContainer->get(Populator::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItMigratesTrackingSettings() {
    // WooCommerce disabled and Tracking enabled
    $this->settings->set('db_version', '3.74.1');
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    // WooCommerce disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    // WooCommerce enabled with cookie enabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    // WooCommerce enabled with cookie disabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_PARTIAL);
    // WooCommerce enabled with cookie disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    // WooCommerce enabled with cookie enabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
  }
}
