<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Editor\LayoutHelper as L;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails\Renderer;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Generic.Files.OneClassPerFile.MultipleFound, PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * NewsletterEntity implements __clone which resets the id, but we need this id to perform a test when stubbing Renderer
 * @ORM\Entity()
 * @ORM\Table(name="newsletters")
 */
class NewsletterEntityWithoutClone extends NewsletterEntity {
  public function __clone() {
  }
}

/**
 * @group woo
 */
class TransactionalEmailHooksTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItCanReplaceWoocommerceEmailStyles() {
    $newsletter = new NewsletterEntity;
    $newsletter->setType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL);
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
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, $newsletter->getId());

    // Set woo options
    $this->wp->updateOption('woocommerce_email_background_color', 'white');
    $this->wp->updateOption('woocommerce_email_base_color', 'red');
    $this->wp->updateOption('woocommerce_email_body_background_color', 'blue');
    $this->wp->updateOption('woocommerce_email_text_color', 'black');

    verify($this->wp->getOption('woocommerce_email_background_color'))->equals('white');
    verify($this->wp->getOption('woocommerce_email_base_color'))->equals('red');
    verify($this->wp->getOption('woocommerce_email_body_background_color'))->equals('blue');
    verify($this->wp->getOption('woocommerce_email_text_color'))->equals('black');

    $transactionalEmails = $this->diContainer->get(TransactionalEmailHooks::class);
    $transactionalEmails->overrideStylesForWooEmails();

    verify($this->wp->getOption('woocommerce_email_background_color'))->equals('#777777');
    verify($this->wp->getOption('woocommerce_email_base_color'))->equals('#888888');
    verify($this->wp->getOption('woocommerce_email_body_background_color'))->equals('#666666');
    verify($this->wp->getOption('woocommerce_email_text_color'))->equals('#111111');
  }

  public function testItDoesntReplaceWoocommerceEmailStylesIfEmailIsNotSet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, null);
    // Set woo options
    $this->wp->updateOption('woocommerce_email_background_color', 'white');
    $this->wp->updateOption('woocommerce_email_base_color', 'red');
    $this->wp->updateOption('woocommerce_email_body_background_color', 'blue');
    $this->wp->updateOption('woocommerce_email_text_color', 'black');

    $transactionalEmails = $this->diContainer->get(TransactionalEmailHooks::class);
    $transactionalEmails->overrideStylesForWooEmails();

    verify($this->wp->getOption('woocommerce_email_background_color'))->equals('white');
    verify($this->wp->getOption('woocommerce_email_base_color'))->equals('red');
    verify($this->wp->getOption('woocommerce_email_body_background_color'))->equals('blue');
    verify($this->wp->getOption('woocommerce_email_text_color'))->equals('black');
  }

  public function testUseTemplateForWCEmails() {
    $addedActions = [];
    $removedActions = [];

    $newsletter = new NewsletterEntityWithoutClone;
    $newsletter->setType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL);
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
      'render' => function(NewsletterEntity $entity, $subject) use(&$newsletter) {
        verify($entity->getId())->equals($newsletter->getId());
        verify($subject)->equals('heading text');
      },
      'getHTMLBeforeContent' => function() {
        return 'HTML before content.';
      },
      'getHTMLAfterContent' => function() {
        return 'HTML after content';
      },
      'prefixCss' => function($css) {
        return 'prefixed ' . $css;
      },
    ]);
    $wcEmails = $this->makeEmpty("\WC_Emails");

    $transactionalEmails = new TransactionalEmailHooks(
      $wp,
      $this->settings,
      $renderer,
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(TransactionalEmails::class)
    );
    $transactionalEmails->useTemplateForWoocommerceEmails();
    verify($addedActions)->arrayCount(1);
    verify($addedActions['woocommerce_email'])->isCallable();
    $addedActions['woocommerce_email']($wcEmails);
    verify($removedActions)->arrayCount(2);
    verify($addedActions)->arrayCount(4);
    verify($addedActions['woocommerce_email_header'])->isCallable();
    ob_start();
    $addedActions['woocommerce_email_header']('heading text');
    verify(ob_get_clean())->equals('HTML before content.');
    verify($addedActions['woocommerce_email_footer'])->isCallable();
    ob_start();
    $addedActions['woocommerce_email_footer']();
    verify(ob_get_clean())->equals('HTML after content');
    verify($addedActions['woocommerce_email_styles'])->isCallable();
    verify($addedActions['woocommerce_email_styles']('some css'))->equals('prefixed some css');
  }

  public function testUseTemplateForWCEmailsWorksWithNoEmail() {
    $addedActions = [];
    $removedActions = [];

    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 2);
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

    new TransactionalEmailHooks(
      $wp,
      $this->settings,
      $this->diContainer->get(Renderer::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(TransactionalEmails::class)
    );

  }
}
