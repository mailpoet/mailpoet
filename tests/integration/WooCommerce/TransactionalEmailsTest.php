<?php

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class TransactionalEmailsTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var array */
  private $original_wc_settings;

  /** @var TransactionalEmails */
  private $transactional_emails;

  function _before() {
    $this->wp = new WPFunctions();
    $this->settings = new SettingsController();
    $this->original_wc_settings = $this->settings->get('woocommerce');
    $this->transactional_emails = new TransactionalEmails($this->wp, $this->settings);
  }

  function testInitCreatesTransactionalEmailAndSavesItsId() {
    $this->transactional_emails->init();
    $email = Newsletter::where('type', Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL)->findOne();
    $id = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID, null);
    expect($email)->notEmpty();
    expect($id)->notNull();
    expect($email->id)->equals($id);
  }

  function testInitDoesntCreateTransactionalEmailIfSettingAlreadySet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 1);
    $this->transactional_emails->init();
    $email = Newsletter::where('type', Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL)->findOne();
    expect($email)->equals(null);
  }

  function testInitUsesImageFromWCSettings() {
    $wp = Stub::make(new WPFunctions, ['getOption' => function($name) {
      if ($name == 'woocommerce_email_header_image') {
        return 'my-awesome-image-url';
      }
    }]);
    $transactional_emails = new TransactionalEmails($wp, $this->settings);
    $transactional_emails->init();
    $email = Newsletter::where('type', Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL)->findOne();
    expect($email)->notEmpty();
    expect($email->body)->contains('my-awesome-image-url');
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    $this->settings->set('woocommerce', $this->original_wc_settings);
  }
}
