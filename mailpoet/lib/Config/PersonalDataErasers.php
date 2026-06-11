<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Subscribers\SubscriberPersonalDataEraser;
use MailPoet\WooCommerce\OrderAttributionPrivacy;
use MailPoet\WP\Functions as WPFunctions;

class PersonalDataErasers {
  public function init() {
    WPFunctions::get()->addFilter('wp_privacy_personal_data_erasers', [$this, 'registerSubscriberEraser']);
    WPFunctions::get()->addFilter('wp_privacy_personal_data_erasers', [$this, 'registerWooCommerceOrderAttributionEraser']);
  }

  public function registerSubscriberEraser($erasers) {
    $erasers['mailpet-subscriber'] = [
      'eraser_friendly_name' => __('MailPoet Subscribers', 'mailpoet'),
      'callback' => [ContainerWrapper::getInstance()->get(SubscriberPersonalDataEraser::class), 'erase'],
    ];

    return $erasers;
  }

  public function registerWooCommerceOrderAttributionEraser($erasers) {
    $erasers['mailpoet-woocommerce-order-attribution'] = [
      'eraser_friendly_name' => __('MailPoet WooCommerce Order Attribution', 'mailpoet'),
      'callback' => [ContainerWrapper::getInstance()->get(OrderAttributionPrivacy::class), 'erase'],
    ];

    return $erasers;
  }
}
