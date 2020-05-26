<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Functions as WPFunctions;

class SendPreviewController {
  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Mailer $mailer,
    MetaInfo $mailerMetaInfo,
    WPFunctions $wp
  ) {
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->wp = $wp;
  }

  public function sendPreview(NewsletterEntity $newsletter, string $emailAddress) {
    // Renderer and Shortcodes need old Newsletter model, until they're rewritten to use Doctrine
    $newsletterModel = Newsletter::findOne($newsletter->getId());
    if (!$newsletterModel) {
      throw new SendPreviewException("Newsletter with ID '{$newsletter->getId()}' not found");
    }

    $renderer = new Renderer();
    $renderedNewsletter = $renderer->render($newsletterModel, $preview = true);
    $divider = '***MailPoet***';
    $dataForShortcodes = array_merge(
      [$newsletter->getSubject()],
      $renderedNewsletter
    );

    $body = implode($divider, $dataForShortcodes);

    $subscriber = Subscriber::getCurrentWPUser() ?: false;
    $shortcodes = new Shortcodes(
      $newsletterModel,
      $subscriber,
      $queue = false,
      $wpUserPreview = true
    );

    list(
      $renderedNewsletter['subject'],
      $renderedNewsletter['body']['html'],
      $renderedNewsletter['body']['text']
    ) = explode($divider, $shortcodes->replace($body));
    $renderedNewsletter['id'] = $newsletter->getId();

    $extraParams = [
      'unsubscribe_url' => $this->wp->homeUrl(),
      'meta' => $this->mailerMetaInfo->getPreviewMetaInfo(),
    ];

    $result = $this->mailer->send($renderedNewsletter, $emailAddress, $extraParams);
    if ($result['response'] === false) {
      $error = sprintf(
        __('The email could not be sent: %s', 'mailpoet'),
        $result['error']->getMessage()
      );
      throw new SendPreviewException($error);
    }
  }
}
