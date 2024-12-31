<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use MailPoet\EmailEditor\Engine\Personalizer;
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
    Personalizer $personalizer
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
    $this->subscribersRepository = $subscribersRepository;
    $this->personalizer = $personalizer;
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
      $this->personalizer->set_context([
        'recipient_email' => $subscriber ? $subscriber->getEmail() : $emailAddress,
        'newsletter_id' => $newsletter->getId(),
        'is_preview' => true,
      ]);
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
}
