<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

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

  public function sendPreview(Newsletter $newsletter, string $emailAddress) {
    $renderer = new Renderer($newsletter, $preview = true);
    $renderedNewsletter = $renderer->render();
    $divider = '***MailPoet***';
    $dataForShortcodes = array_merge(
      [$newsletter->subject],
      $renderedNewsletter
    );

    $body = implode($divider, $dataForShortcodes);

    $subscriber = Subscriber::getCurrentWPUser() ?: false;
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue = false,
      $wpUserPreview = true
    );

    list(
      $renderedNewsletter['subject'],
      $renderedNewsletter['body']['html'],
      $renderedNewsletter['body']['text']
    ) = explode($divider, $shortcodes->replace($body));
    $renderedNewsletter['id'] = $newsletter->id;

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
