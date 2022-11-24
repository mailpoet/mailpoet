<?php

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails\Template;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
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
    $this->entityManager
      ->createQueryBuilder()
      ->delete()
      ->from(NewsletterEntity::class, 'n')
      ->getQuery()
      ->execute();
    $this->entityManager
      ->createQueryBuilder()
      ->delete()
      ->from(SettingEntity::class, 's')
      ->getQuery()
      ->execute();
    $this->wp = new WPFunctions();
    $this->settings = SettingsController::getInstance();
    $this->originalWcSettings = $this->settings->get('woocommerce');
    $this->newslettersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->transactionalEmails = new TransactionalEmails(
      $this->wp,
      $this->settings,
      ContainerWrapper::getInstance()->get(Template::class),
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newslettersRepository
    );
    $this->settings->set('woocommerce', $this->originalWcSettings);
  }

  public function testInitCreatesTransactionalEmailAndSavesItsId() {
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $this->assertInstanceOf(NewsletterEntity::class, $email);
    $id = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID, null);
    expect($email)->notEmpty();
    expect($id)->notNull();
    expect($email->getId())->equals($id);
  }

  public function testInitDoesntCreateTransactionalEmailIfSettingAlreadySet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 1);
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
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
      Stub::makeEmpty(WooCommerceHelper::class),
      $this->newslettersRepository
    );
    $transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy([
      'type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL,
    ]);
    $this->assertInstanceOf(NewsletterEntity::class, $email);
    expect($email)->notEmpty();
    expect(json_encode($email->getBody()))->stringContainsString('my-awesome-image-url');
  }

  public function testInitStripsUnwantedTagsFromWCFooterText() {
    $optionOriginalValue = $this->wp->getOption('woocommerce_email_footer_text');
    $this->wp->updateOption('woocommerce_email_footer_text', '<div><p>Text <a href="http://example.com">Link</a></p></div>');
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $this->assertInstanceOf(NewsletterEntity::class, $email);
    $body = $email->getBody();
    assert(is_array($body));
    $footerTextBlock = $body['content']['blocks'][5]['blocks'][0]['blocks'][1];
    expect($footerTextBlock['text'])->equals('<p style="text-align: center;">Text <a href="http://example.com">Link</a></p>');
    $this->wp->updateOption('woocommerce_email_footer_text', $optionOriginalValue);
  }
}
