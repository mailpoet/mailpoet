<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class SendPreviewController {
  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var WPFunctions */
  private $wp;

  /** @var Renderer */
  private $renderer;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var Personalizer */
  private $personalizer;

  public function __construct(
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    Renderer $renderer,
    WPFunctions $wp,
    SubscribersRepository $subscribersRepository,
    Shortcodes $shortcodes,
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
    $this->subscribersRepository = $subscribersRepository;
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
  }

  public function sendPreview(NewsletterEntity $newsletter, string $emailAddress) {
    $renderedNewsletter = $this->renderer->renderAsPreview($newsletter);
    $divider = '***MailPoet***';
    $dataForShortcodes = array_merge(
      [$newsletter->getSubject()],
      $renderedNewsletter
    );

    $body = implode($divider, $dataForShortcodes);

    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    $this->shortcodes->setNewsletter($newsletter);
    if ($subscriber instanceof SubscriberEntity) {
      $this->shortcodes->setSubscriber($subscriber);
    }
    $this->shortcodes->setWpUserPreview(true);

    [
      $renderedNewsletter['subject'],
      $renderedNewsletter['body']['html'],
      $renderedNewsletter['body']['text'],
    ] = explode($divider, $this->shortcodes->replace($body));

    if ($newsletter->getWpPostId()) {
      $context = [
        'recipient_email' => $subscriber ? $subscriber->getEmail() : $emailAddress,
        'newsletter_id' => $newsletter->getId(),
        'is_preview' => true,
      ];

      // For automation emails, add sample WooCommerce order/customer data for preview
      if ($newsletter->isAutomation() || $newsletter->isAutomationTransactional()) {
        $automationId = $newsletter->getOptionValue('automationId');
        if ($automationId) {
          $context = $this->addSampleDataToContext($context);
        }
      }

      $this->personalizer->set_context($context);
      $renderedNewsletter['subject'] = $this->personalizer->personalize_content($renderedNewsletter['subject']);
      $renderedNewsletter['body']['html'] = $this->personalizer->personalize_content($renderedNewsletter['body']['html']);
      $renderedNewsletter['body']['text'] = $this->personalizer->personalize_content($renderedNewsletter['body']['text']);
    }

    $renderedNewsletter['id'] = $newsletter->getId();

    $extraParams = [
      'unsubscribe_url' => $this->wp->homeUrl(),
      'meta' => $this->mailerMetaInfo->getPreviewMetaInfo(),
    ];

    $result = $this->mailerFactory->getDefaultMailer()->send($renderedNewsletter, $emailAddress, $extraParams);
    if ($result['response'] === false) {
      $error = sprintf(
        // translators: %s contains the actual error message.
        __('The email could not be sent: %s', 'mailpoet'),
        $result['error']->getMessage()
      );
      throw new SendPreviewException($error);
    }
  }

  /**
   * Add sample WooCommerce order/customer data to context for preview.
   * Uses WooCommerce's dummy data for consistent, predictable previews.
   *
   * @param array $context Existing context
   * @return array Context with sample data added
   */
  private function addSampleDataToContext(array $context): array {
    $dummyOrder = $this->getWooCommerceDummyOrder();
    if ($dummyOrder instanceof \WC_Order) {
      $context['order'] = $dummyOrder;
    }

    $dummyCustomer = $this->getWooCommerceDummyCustomer();
    if ($dummyCustomer instanceof \WC_Customer) {
      $context['customer'] = $dummyCustomer;
    }

    return $context;
  }

  /**
   * Get a dummy WooCommerce order using WooCommerce's EmailPreview class.
   * This reuses WooCommerce's placeholder generation logic.
   *
   * @return \WC_Order|null Dummy order or null if unavailable
   */
  private function getWooCommerceDummyOrder(): ?\WC_Order {
    // Check if WooCommerce EmailPreview class exists
    if (!class_exists('Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview')) {
      return null;
    }

    try {
      // Access WooCommerce EmailPreview singleton
      $emailPreview = \Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview::instance();

      // Use reflection to call the private get_dummy_order() method
      $reflectionClass = new \ReflectionClass($emailPreview);
      $method = $reflectionClass->getMethod('get_dummy_order');
      $method->setAccessible(true);

      $dummyOrder = $method->invoke($emailPreview);

      return $dummyOrder instanceof \WC_Order ? $dummyOrder : null;
    } catch (\Throwable $e) {
      // If reflection fails, try the filter as fallback
      $dummyOrder = $this->wp->applyFilters('woocommerce_email_preview_dummy_order', null, null);
      return $dummyOrder instanceof \WC_Order ? $dummyOrder : null;
    }
  }

  /**
   * Get a dummy WooCommerce customer using WooCommerce's dummy address data.
   * Creates a WC_Customer with placeholder data matching WooCommerce's approach.
   *
   * @return \WC_Customer|null Dummy customer or null if unavailable
   */
  private function getWooCommerceDummyCustomer(): ?\WC_Customer {
    if (!class_exists(\WC_Customer::class)) {
      return null;
    }

    try {
      $dummyAddress = $this->wp->applyFilters('woocommerce_email_preview_dummy_address', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'company' => 'Company',
        'email' => 'john@company.com',
        'phone' => '555-555-5555',
        'address_1' => '123 Fake Street',
        'city' => 'Faketown',
        'postcode' => '12345',
        'country' => 'US',
        'state' => 'CA',
      ], null);

      // Ensure the filter result is an array
      if (!is_array($dummyAddress)) {
        $dummyAddress = [];
      }

      // Create a dummy customer with WooCommerce's placeholder data
      $customer = new \WC_Customer();
      $customer->set_id(0); // Mark as unsaved
      $customer->set_first_name((string)($dummyAddress['first_name'] ?? 'John'));
      $customer->set_last_name((string)($dummyAddress['last_name'] ?? 'Doe'));
      $customer->set_email((string)($dummyAddress['email'] ?? 'john@company.com'));
      $customer->set_billing_first_name((string)($dummyAddress['first_name'] ?? 'John'));
      $customer->set_billing_last_name((string)($dummyAddress['last_name'] ?? 'Doe'));
      $customer->set_billing_company((string)($dummyAddress['company'] ?? 'Company'));
      $customer->set_billing_address_1((string)($dummyAddress['address_1'] ?? '123 Fake Street'));
      $customer->set_billing_city((string)($dummyAddress['city'] ?? 'Faketown'));
      $customer->set_billing_state((string)($dummyAddress['state'] ?? 'CA'));
      $customer->set_billing_postcode((string)($dummyAddress['postcode'] ?? '12345'));
      $customer->set_billing_country((string)($dummyAddress['country'] ?? 'US'));
      $customer->set_billing_phone((string)($dummyAddress['phone'] ?? '555-555-5555'));
      $customer->set_billing_email((string)($dummyAddress['email'] ?? 'john@company.com'));

      // Set shipping address (same as billing for dummy data)
      $customer->set_shipping_first_name((string)($dummyAddress['first_name'] ?? 'John'));
      $customer->set_shipping_last_name((string)($dummyAddress['last_name'] ?? 'Doe'));
      $customer->set_shipping_company((string)($dummyAddress['company'] ?? 'Company'));
      $customer->set_shipping_address_1((string)($dummyAddress['address_1'] ?? '123 Fake Street'));
      $customer->set_shipping_city((string)($dummyAddress['city'] ?? 'Faketown'));
      $customer->set_shipping_state((string)($dummyAddress['state'] ?? 'CA'));
      $customer->set_shipping_postcode((string)($dummyAddress['postcode'] ?? '12345'));
      $customer->set_shipping_country((string)($dummyAddress['country'] ?? 'US'));

      return $customer;
    } catch (\Throwable $e) {
      return null;
    }
  }
}
