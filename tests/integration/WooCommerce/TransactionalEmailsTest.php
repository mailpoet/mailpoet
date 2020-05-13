<?php

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
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

class TransactionalEmailsTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var array */
  private $originalWcSettings;

  /** @var TransactionalEmails */
  private $transactionalEmails;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    $this->wp = new WPFunctions();
    $this->settings = SettingsController::getInstance();
    $this->originalWcSettings = $this->settings->get('woocommerce');
    $this->newslettersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->transactionalEmails = new TransactionalEmails(
      $this->wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newslettersRepository
    );
  }

  public function testInitCreatesTransactionalEmailAndSavesItsId() {
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $id = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID, null);
    expect($email)->notEmpty();
    expect($id)->notNull();
    expect($email->getId())->equals($id);
  }

  public function testInitDoesntCreateTransactionalEmailIfSettingAlreadySet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 1);
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL]);
    expect($email)->equals(null);
  }

  public function testInitUsesImageFromWCSettings() {
    $wp = Stub::make(new WPFunctions, ['getOption' => function($name) {
      if ($name == 'woocommerce_email_header_image') {
        return 'my-awesome-image-url';
      }
    }]);
    $transactionalEmails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newslettersRepository
    );
    $transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy([
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
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();

    $options = [];
    $wp = Stub::make(new WPFunctions, [
      'updateOption' => function($name, $value) use (&$options) {
        $options[$name] = $value;
      },
      'getOption' => function($name) use (&$options) {
        return $options[$name];
      },
    ]);

    $transactionalEmails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      ContainerWrapper::getInstance()->get(Renderer::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newslettersRepository
    );
    $transactionalEmails->enableEmailSettingsSyncToWooCommerce();

    $newsletterData = $this->diContainer->get(NewslettersResponseBuilder::class)->build($newsletter);
    $wp->applyFilters('mailpoet_api_newsletters_save_after', $newsletterData);

    expect($wp->getOption('woocommerce_email_background_color'))->equals('#777777');
    expect($wp->getOption('woocommerce_email_base_color'))->equals('#888888');
    expect($wp->getOption('woocommerce_email_body_background_color'))->equals('#666666');
    expect($wp->getOption('woocommerce_email_text_color'))->equals('#111111');
  }

  public function testUseTemplateForWCEmails() {
    $addedActions = [];
    $removedActions = [];
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
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, $newsletter->getId());
    $wp = Stub::make(new WPFunctions, [
      'getOption' => function($name) {
        return '';
      },
      'addAction' => function ($name, $action) use(&$addedActions) {
        $addedActions[$name] = $action;
      },
      'removeAction' => function ($name, $action) use(&$removedActions) {
        $removedActions[$name] = $action;
      },
    ]);
    $renderer = Stub::make(Renderer::class, [
      'render' => function($email) use(&$newsletter) {
        expect($email->id)->equals($newsletter->getId());
      },
      'getHTMLBeforeContent' => function($headingText) {
        return 'HTML before content with ' . $headingText;
      },
      'getHTMLAfterContent' => function() {
        return 'HTML after content';
      },
      'prefixCss' => function($css) {
        return 'prefixed ' . $css;
      },
    ]);

    $transactionalEmails = new TransactionalEmails(
      $wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      $renderer,
      Stub::makeEmpty(WooCommerceHelper::class),
      ContainerWrapper::getInstance()->get(NewslettersRepository::class)
    );
    $transactionalEmails->useTemplateForWoocommerceEmails();
    expect($addedActions)->count(1);
    expect($addedActions['woocommerce_init'])->callable();
    $addedActions['woocommerce_init']();
    expect($removedActions)->count(2);
    expect($addedActions)->count(4);
    expect($addedActions['woocommerce_email_header'])->callable();
    ob_start();
    $addedActions['woocommerce_email_header']('heading text');
    expect(ob_get_clean())->equals('HTML before content with heading text');
    expect($addedActions['woocommerce_email_footer'])->callable();
    ob_start();
    $addedActions['woocommerce_email_footer']();
    expect(ob_get_clean())->equals('HTML after content');
    expect($addedActions['woocommerce_email_styles'])->callable();
    expect($addedActions['woocommerce_email_styles']('some css'))->equals('prefixed some css');
  }

  public function testInitStripsUnwantedTagsFromWCFooterText() {
    $optionOriginalValue = $this->wp->getOption('woocommerce_email_footer_text');
    $this->wp->updateOption('woocommerce_email_footer_text', '<div><p>Text <a href="http://example.com">Link</a></p></div>');
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL]);
    assert($email instanceof NewsletterEntity);
    $body = $email->getBody();
    assert(is_array($body));
    $footerTextBlock = $body['content']['blocks'][5]['blocks'][0]['blocks'][1];
    expect($footerTextBlock['text'])->equals('<p style="text-align: center;">Text <a href="http://example.com">Link</a></p>');
    $this->wp->updateOption('woocommerce_email_footer_text', $optionOriginalValue);
  }

  public function _after() {
    $this->entityManager
      ->createQueryBuilder()
      ->delete()
      ->from(NewsletterEntity::class, 'n')
      ->getQuery()
      ->execute();
    $this->settings->set('woocommerce', $this->originalWcSettings);
  }
}
