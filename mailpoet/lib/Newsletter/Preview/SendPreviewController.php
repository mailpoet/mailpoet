<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTagManager;
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

  /** @var PersonalizationTagManager */
  private $personalizationTagManager;

  /** @var WooCommerceDummyData */
  private $wooCommerceDummyData;

  public function __construct(
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    Renderer $renderer,
    WPFunctions $wp,
    SubscribersRepository $subscribersRepository,
    Shortcodes $shortcodes,
    PersonalizationTagManager $personalizationTagManager,
    WooCommerceDummyData $wooCommerceDummyData
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
    $this->subscribersRepository = $subscribersRepository;
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
    $this->personalizationTagManager = $personalizationTagManager;
    $this->wooCommerceDummyData = $wooCommerceDummyData;
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
          // Extend personalization tags based on automation subjects before personalizing
          $this->personalizationTagManager->extendPersonalizationTagsByAutomationSubjects((int)$automationId);
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
   *
   * @param array<string, mixed> $context Existing context
   * @return array<string, mixed> Context with sample data added
   */
  private function addSampleDataToContext(array $context): array {
    $order = $this->wooCommerceDummyData->getOrder();
    if ($order instanceof \WC_Order) {
      $context['order'] = $order;
    }

    $customer = $this->wooCommerceDummyData->getCustomer();
    if ($customer instanceof \WC_Customer) {
      $context['customer'] = $customer;
    }

    // Allow extensions (like premium plugin) to add additional sample data for preview
    /** @var array<string, mixed> $context */
    $context = $this->wp->applyFilters('mailpoet_automation_email_preview_sample_data', $context);

    return $context;
  }
}
