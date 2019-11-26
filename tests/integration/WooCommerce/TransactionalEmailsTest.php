<?php

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails\Renderer;
use MailPoet\WooCommerce\TransactionalEmails\Template;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

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
    $this->settings = SettingsController::getInstance();
    $this->original_wc_settings = $this->settings->get('woocommerce');
    $this->transactional_emails = new TransactionalEmails(
      $this->wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      ContainerWrapper::getInstance()->get(NewslettersRepository::class)
    );
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
    $transactional_emails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      ContainerWrapper::getInstance()->get(NewslettersRepository::class)
    );
    $transactional_emails->init();
    $email = Newsletter::where('type', Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL)->findOne();
    expect($email)->notEmpty();
    expect($email->body)->contains('my-awesome-image-url');
  }

  function testUseTemplateForWCEmails() {
    $added_actions = [];
    $removed_actions = [];
    $newsletter = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL,
      'subject' => 'WooCommerce Transactional Email',
      'preheader' => '',
      'body' => json_encode([
        'content' => L::col([
          L::row([L::col([['type' => 'text', 'text' => 'Some text before heading']])]),
          ['type' => 'woocommerceHeading'],
          L::row([L::col([['type' => 'text', 'text' => 'Some text between heading and content']])]),
          ['type' => 'woocommerceContent'],
          L::row([L::col([['type' => 'text', 'text' => 'Some text after content']])]),
        ]),
      ]),
    ]);
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, $newsletter->id);
    $wp = Stub::make(new WPFunctions, [
      'getOption' => function($name) {
        return '';
      },
      'addAction' => function ($name, $action) use(&$added_actions) {
        $added_actions[$name] = $action;
      },
      'removeAction' => function ($name, $action) use(&$removed_actions) {
        $removed_actions[$name] = $action;
      },
    ]);
    $renderer = Stub::make(Renderer::class, [
      'render' => function($email) use(&$newsletter) {
        expect($email->id)->equals($newsletter->id);
      },
      'getHTMLBeforeContent' => function($heading_text) {
        return 'HTML before content with ' . $heading_text;
      },
      'getHTMLAfterContent' => function() {
        return 'HTML after content';
      },
      'prefixCss' => function($css) {
        return 'prefixed ' . $css;
      },
    ]);

    $transactional_emails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      $renderer,
      ContainerWrapper::getInstance()->get(NewslettersRepository::class)
    );
    $transactional_emails->useTemplateForWoocommerceEmails();
    expect($added_actions)->count(1);
    expect($added_actions['woocommerce_init'])->isCallable();
    $added_actions['woocommerce_init']();
    expect($removed_actions)->count(2);
    expect($added_actions)->count(4);
    expect($added_actions['woocommerce_email_header'])->isCallable();
    ob_start();
    $added_actions['woocommerce_email_header']('heading text');
    expect(ob_get_clean())->equals('HTML before content with heading text');
    expect($added_actions['woocommerce_email_footer'])->isCallable();
    ob_start();
    $added_actions['woocommerce_email_footer']();
    expect(ob_get_clean())->equals('HTML after content');
    expect($added_actions['woocommerce_email_styles'])->isCallable();
    expect($added_actions['woocommerce_email_styles']('some css'))->equals('prefixed some css');
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    $this->settings->set('woocommerce', $this->original_wc_settings);
  }
}
