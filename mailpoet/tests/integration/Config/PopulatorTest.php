<?php declare(strict_types=1);

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\Notices\ChangedTrackingNotice;
use MailPoet\WP\Functions;

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
    $wp = $this->diContainer->get(Functions::class);
    $wp->deleteTransient(ChangedTrackingNotice::OPTION_NAME);
    // WooCommerce disabled and Tracking enabled
    $this->settings->set('db_version', '3.74.1');
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', null);
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie enabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie disabled and Tracking enabled
    $this->settings->set('tracking', ['enabled' => true]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_PARTIAL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie disabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_BASIC);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->false();
    // WooCommerce enabled with cookie enabled and Tracking disabled
    $this->settings->set('tracking', ['enabled' => false]);
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->populator->up();
    expect($this->settings->get('tracking.level'))->equals(TrackingConfig::LEVEL_FULL);
    expect($wp->getTransient(ChangedTrackingNotice::OPTION_NAME))->equals(true);
  }
}
