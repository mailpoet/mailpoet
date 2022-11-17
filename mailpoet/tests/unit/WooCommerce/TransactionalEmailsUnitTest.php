<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails\Template;
use MailPoet\WP\Functions as WPFunctions;

class TransactionalEmailsUnitTest extends \MailPoetUnitTest {
  public function testGetEmailHeadings() {
    $wp = Stub::make(new WPFunctions, [
      'getOption' => function($name) {
        if ($name === 'woocommerce_new_order_settings')
        return ['heading' => '{site_title}: New Order: #{order_number}'];
        if ($name === 'woocommerce_customer_completed_order_settings')
        return ['heading' => 'Thanks for shopping at {site_address}'];
        if ($name === 'woocommerce_customer_note_settings')
          return ['heading' => 'Note added to order #{order_number} - {order_date}'];
        if ($name === 'blogname')
          return 'Test';
        return false;
      },
      'homeUrl' => 'http://test.loc',
      'wpSpecialcharsDecode' => function($text) {
        expect($text)->equals('Test');
        return $text;
      },
      'wpParseUrl' => function($url) {
        expect($url)->equals('http://test.loc');
        return 'test.loc';
      },
    ]);
    $settings = Stub::make(SettingsController::class);
    $template = Stub::make(Template::class);
    $woocommerceHelper = Stub::make(WooCommerceHelper::class);
    $newslettersRepository = Stub::make(NewslettersRepository::class);
    $transactionalEmails = new TransactionalEmails($wp, $settings, $template, $woocommerceHelper, $newslettersRepository);
    expect($transactionalEmails->getEmailHeadings())->equals([
      'new_account' => 'Test: New Order: #0001',
      'processing_order' => 'Thank you for your order',
      'completed_order' => 'Thanks for shopping at test.loc',
      'customer_note' => 'Note added to order #0001 - ' . date('Y-m-d'),
    ]);
  }
}
