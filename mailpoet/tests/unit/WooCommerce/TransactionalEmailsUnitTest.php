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
        verify($text)->equals('Test');
        return $text;
      },
      'wpParseUrl' => function($url) {
        verify($url)->equals('http://test.loc');
        return 'test.loc';
      },
    ]);
    $settings = Stub::make(SettingsController::class);
    $template = Stub::make(Template::class);
    $woocommerceHelper = Stub::make(WooCommerceHelper::class);
    $newslettersRepository = Stub::make(NewslettersRepository::class);
    $transactionalEmails = new TransactionalEmails($wp, $settings, $template, $woocommerceHelper, $newslettersRepository);
    verify($transactionalEmails->getEmailHeadings())->equals([
      'new_account' => 'Test: New Order: #0001',
      'processing_order' => 'Thank you for your order',
      'completed_order' => 'Thanks for shopping at test.loc',
      'customer_note' => 'Note added to order #0001 - ' . date('Y-m-d'),
    ]);
  }

  public function testGetWCEmailSettingsResolvesStoreAddressAndOtherWooCommercePlaceholdersInFooterText() {
    $wp = Stub::make(new WPFunctions, [
      'getOption' => function($name) {
        if ($name === 'woocommerce_email_footer_text') {
          return '{site_title} - {store_address} - {store_email} - {site_url} - {woocommerce}';
        }
        if ($name === 'blogname') {
          return 'Test';
        }
        return false;
      },
      'homeUrl' => 'http://test.loc',
      'wpSpecialcharsDecode' => function($text) {
        return $text;
      },
      'wpParseUrl' => function($url) {
        return 'test.loc';
      },
    ]);
    $settings = Stub::make(SettingsController::class);
    $template = Stub::make(Template::class);
    $woocommerceHelper = Stub::make(WooCommerceHelper::class, [
      'wcGetStoreAddress' => '123 Main St, Springfield',
      'wcGetStoreEmail' => 'store@example.com',
      'wcLightOrDark' => '#202020',
      'wcHexIsLight' => true,
    ]);
    $newslettersRepository = Stub::make(NewslettersRepository::class);
    $transactionalEmails = new TransactionalEmails($wp, $settings, $template, $woocommerceHelper, $newslettersRepository);
    $footerText = $transactionalEmails->getWCEmailSettings()['footer_text'];
    verify($footerText)->equals('Test - 123 Main St, Springfield - store@example.com - test.loc - WooCommerce');
  }
}
