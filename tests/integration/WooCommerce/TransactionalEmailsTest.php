<?php

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
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

  /** @var NewslettersRepository */
  private $newsletters_repository;

  public function _before() {
    $this->wp = new WPFunctions();
    $this->settings = SettingsController::getInstance();
    $this->original_wc_settings = $this->settings->get('woocommerce');
    $this->newsletters_repository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->transactional_emails = new TransactionalEmails(
      $this->wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newsletters_repository
    );
  }

  public function testInitCreatesTransactionalEmailAndSavesItsId() {
    $this->transactional_emails->init();
    $email = $this->newsletters_repository->findOneBy(['type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $id = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID, null);
    expect($email)->notEmpty();
    expect($id)->notNull();
    expect($email->getId())->equals($id);
  }

  public function testInitDoesntCreateTransactionalEmailIfSettingAlreadySet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 1);
    $this->transactional_emails->init();
    $email = $this->newsletters_repository->findOneBy(['type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL]);
    expect($email)->equals(null);
  }

  public function testInitUsesImageFromWCSettings() {
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
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newsletters_repository
    );
    $transactional_emails->init();
    $email = $this->newsletters_repository->findOneBy([
      'type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL,
    ]);
    expect($email)->notEmpty();
    expect(json_encode($email->getBody()))->contains('my-awesome-image-url');
  }

  public function testItSynchronizesEmailSettingsToWooCommerce() {
    $newsletter = new NewsletterEntity;
    $newsletter->setType(Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL);
    $newsletter->setSubject('WooCommerce Transactional Email');
    $newsletter->setBody([
      'globalStyles' => [
        'text' =>
         [
          'fontColor' => '#111111',
          'fontFamily' => 'Arial',
          'fontSize' => '14px',
          'lineHeight' => '1.6',
         ],
        'h1' =>
         [
          'fontColor' => '#222222',
          'fontFamily' => 'Source Sans Pro',
          'fontSize' => '36px',
          'lineHeight' => '1.6',
         ],
        'h2' =>
         [
          'fontColor' => '#333333',
          'fontFamily' => 'Verdana',
          'fontSize' => '24px',
          'lineHeight' => '1.6',
         ],
        'h3' =>
         [
          'fontColor' => '#444444',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '22px',
          'lineHeight' => '1.6',
         ],
        'link' =>
         [
          'fontColor' => '#555555',
          'textDecoration' => 'underline',
         ],
        'wrapper' =>
         [
          'backgroundColor' => '#666666',
         ],
        'body' =>
         [
          'backgroundColor' => '#777777',
         ],
        'woocommerce' =>
         [
          'brandingColor' => '#888888',
          'headingFontColor' => '#999999',
         ],
      ],
    ]);
    $this->newsletters_repository->persist($newsletter);
    $this->newsletters_repository->flush();

    $options = [];
    $wp = Stub::make(new WPFunctions, [
      'updateOption' => function($name, $value) use (&$options) {
        $options[$name] = $value;
      },
      'getOption' => function($name) use (&$options) {
        return $options[$name];
      },
    ]);

    $transactional_emails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newsletters_repository
    );
    $transactional_emails->enableEmailSettingsSyncToWooCommerce();

    $newsletter = Newsletter::findOne($newsletter->getId());
    $wp->doAction('mailpoet_api_newsletters_save_after', $newsletter);

    expect($wp->getOption('woocommerce_email_background_color'))->equals('#777777');
    expect($wp->getOption('woocommerce_email_base_color'))->equals('#888888');
    expect($wp->getOption('woocommerce_email_body_background_color'))->equals('#666666');
    expect($wp->getOption('woocommerce_email_text_color'))->equals('#111111');
  }

  public function testUseTemplateForWCEmails() {
    $added_actions = [];
    $removed_actions = [];
    $newsletter = new NewsletterEntity;
    $newsletter->setType(Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL);
    $newsletter->setSubject('WooCommerce Transactional Email');
    $newsletter->setBody([
      'content' => L::col([
        L::row([L::col([['type' => 'text', 'text' => 'Some text before heading']])]),
        ['type' => 'woocommerceHeading'],
        L::row([L::col([['type' => 'text', 'text' => 'Some text between heading and content']])]),
        ['type' => 'woocommerceContent'],
        L::row([L::col([['type' => 'text', 'text' => 'Some text after content']])]),
      ]),
    ]);
    $this->newsletters_repository->persist($newsletter);
    $this->newsletters_repository->flush();
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, $newsletter->getId());
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
        expect($email->id)->equals($newsletter->getId());
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
      Stub::makeEmpty(WooCommerceHelper::class),
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

  public function _after() {
    $this->entity_manager
      ->createQueryBuilder()
      ->delete()
      ->from(NewsletterEntity::class, 'n')
      ->getQuery()
      ->execute();
    $this->settings->set('woocommerce', $this->original_wc_settings);
  }
}
