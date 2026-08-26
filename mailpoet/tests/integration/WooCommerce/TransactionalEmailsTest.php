<?php declare(strict_types = 1);

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
    verify($email)->notEmpty();
    verify($id)->notNull();
    verify($email->getId())->equals($id);
  }

  public function testInitDoesntCreateTransactionalEmailIfSettingAlreadySet() {
    $this->settings->set(TransactionalEmails::SETTING_EMAIL_ID, 1);
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    verify($email)->equals(null);
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
    verify($email)->notEmpty();
    verify(json_encode($email->getBody()))->stringContainsString('my-awesome-image-url');
  }

  public function testInitStripsUnwantedTagsFromWCFooterText() {
    $optionOriginalValue = $this->wp->getOption('woocommerce_email_footer_text');
    $this->wp->updateOption('woocommerce_email_footer_text', '<div><p>Text <a href="http://example.com">Link</a></p></div>');
    $this->transactionalEmails->init();
    $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
    $this->assertInstanceOf(NewsletterEntity::class, $email);
    $body = $email->getBody();
    $this->assertIsArray($body);
    $footerTextBlock = $body['content']['blocks'][5]['blocks'][0]['blocks'][1];
    verify($footerTextBlock['text'])->equals('<p style="text-align: center;">Text <a href="http://example.com">Link</a></p>');
    $this->wp->updateOption('woocommerce_email_footer_text', $optionOriginalValue);
  }

  public function testInitResolvesStoreAddressPlaceholderInFooterTextUsingRealWooCommerceData() {
    $originalFooterText = $this->wp->getOption('woocommerce_email_footer_text');
    $originalAddress = $this->wp->getOption('woocommerce_store_address');
    $originalCity = $this->wp->getOption('woocommerce_store_city');
    $originalPostcode = $this->wp->getOption('woocommerce_store_postcode');
    $originalCountry = $this->wp->getOption('woocommerce_default_country');
    $originalFromAddress = $this->wp->getOption('woocommerce_email_from_address');

    try {
      $this->wp->updateOption('woocommerce_email_footer_text', '{store_address} - {store_email}');
      $this->wp->updateOption('woocommerce_store_address', '123 Main St');
      $this->wp->updateOption('woocommerce_store_city', 'New York');
      $this->wp->updateOption('woocommerce_store_postcode', '10001');
      $this->wp->updateOption('woocommerce_default_country', 'US:NY');
      $this->wp->updateOption('woocommerce_email_from_address', 'store@example.com');

      $transactionalEmails = new TransactionalEmails(
        $this->wp,
        $this->settings,
        ContainerWrapper::getInstance()->get(Template::class),
        ContainerWrapper::getInstance()->get(WooCommerceHelper::class),
        $this->newslettersRepository
      );
      $transactionalEmails->init();
      $email = $this->newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
      $this->assertInstanceOf(NewsletterEntity::class, $email);
      $body = $email->getBody();
      $this->assertIsArray($body);
      $footerTextBlock = $body['content']['blocks'][5]['blocks'][0]['blocks'][1];
      verify($footerTextBlock['text'])->stringContainsString('New York');
      verify($footerTextBlock['text'])->stringContainsString('10001');
      verify($footerTextBlock['text'])->stringContainsString('store@example.com');
      verify($footerTextBlock['text'])->stringNotContainsString('{store_address}');
      verify($footerTextBlock['text'])->stringNotContainsString('{store_email}');
    } finally {
      $this->wp->updateOption('woocommerce_email_footer_text', $originalFooterText);
      $this->wp->updateOption('woocommerce_store_address', $originalAddress);
      $this->wp->updateOption('woocommerce_store_city', $originalCity);
      $this->wp->updateOption('woocommerce_store_postcode', $originalPostcode);
      $this->wp->updateOption('woocommerce_default_country', $originalCountry);
      $this->wp->updateOption('woocommerce_email_from_address', $originalFromAddress);
    }
  }
}
