<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\WoocommerceSettings;
use MailPoet\WP\Functions as WPFunctions;

class WoocommerceSettingsTest extends \MailPoetTest {

  /** @var WoocommerceSettings */
  private $endpoint;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    $this->wp = new WPFunctions();
    $this->endpoint = new WoocommerceSettings($this->wp);
  }

  public function testItCanSetSettings() {
    $this->wp->updateOption('woocommerce_email_base_color', '#ffffff');
    $response = $this->endpoint->set([
      'woocommerce_email_base_color' => '#aaaaaa',
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($this->wp->getOption('woocommerce_email_base_color'))->equals('#aaaaaa');
  }

  public function testItDoesNotSetUnallowedSettings() {
    $response = $this->endpoint->set([
      'mailpoet_some_none_exting_option' => 'some value',
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($this->wp->getOption('mailpoet_some_none_exting_option', null))->equals(null);
  }
}
