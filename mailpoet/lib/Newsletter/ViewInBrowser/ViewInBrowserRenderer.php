<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\ViewInBrowser;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WP\Emoji;

class ViewInBrowserRenderer {
  /** @var Emoji */
  private $emoji;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var Renderer */
  private $renderer;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var Links */
  private $links;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  private Personalizer $personalizer;

  public function __construct(
    Emoji $emoji,
    TrackingConfig $trackingConfig,
    Shortcodes $shortcodes,
    Renderer $renderer,
    Links $links,
    NewsletterUrl $newsletterUrl
  ) {
    $this->emoji = $emoji;
    $this->trackingConfig = $trackingConfig;
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
    $this->links = $links;
    $this->newsletterUrl = $newsletterUrl;
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
  }

  public function render(
    bool $isPreview,
    NewsletterEntity $newsletter,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null
  ) {
    return $this->renderWithContext(
      $newsletter,
      $subscriber,
      $queue,
      $isPreview,
      $isPreview,
      $isPreview,
      !$isPreview,
      $isPreview,
      false
    );
  }

  public function renderPublicShare(
    NewsletterEntity $newsletter,
    ?SendingQueueEntity $queue = null
  ): string {
    // An empty SubscriberEntity (not null) is intentional: the subscriber
    // shortcode handler treats null as "leave the shortcode in place" but
    // resolves `default:` arguments against any SubscriberEntity instance.
    return $this->renderWithContext(
      $newsletter,
      new SubscriberEntity(),
      $queue,
      false,
      true,
      true,
      false,
      false,
      true
    );
  }

  private function renderWithContext(
    NewsletterEntity $newsletter,
    ?SubscriberEntity $subscriber,
    ?SendingQueueEntity $queue,
    bool $renderAsPreview,
    bool $sanitizeRenderedQueueLinks,
    bool $shortcodesPreview,
    bool $replaceSubscriberTracking,
    bool $personalizerPreview,
    bool $replaceViewInBrowserUrlWithPublicShareUrl
  ): string {
    $isTrackingEnabled = $this->trackingConfig->isEmailTrackingEnabled();

    if ($queue && $queue->getNewsletterRenderedBody()) {
      $body = $queue->getNewsletterRenderedBody();
      if (is_array($body)) {
        $newsletterBody = $body['html'];
      } else {
        $newsletterBody = '';
      }
      $newsletterBody = $this->emoji->decodeEmojisInBody($newsletterBody);
      // rendered newsletter body has shortcodes converted to links; we need to
      // isolate "view in browser", "unsubscribe" and "manage subscription" links
      // and convert them to shortcodes, which later will be replaced with "#" when
      // newsletter is previewed
      if ($sanitizeRenderedQueueLinks && preg_match($this->links->getLinkRegex(), $newsletterBody)) {
        $newsletterBody = $this->links->convertHashedLinksToShortcodesAndUrls(
          $newsletterBody,
          $queue->getId(),
          $convertAll = true
        );
        // remove open tracking link
        $newsletterBody = str_replace(Links::DATA_TAG_OPEN, '', $newsletterBody);
      }
    } else {
      if ($renderAsPreview) {
        $newsletterBody = $this->renderer->renderAsPreview($newsletter, 'html');
      } else {
        $newsletterBody = $this->renderer->render($newsletter, $sendingTask = null, 'html');
      }
    }
    $this->prepareShortcodes(
      $newsletter,
      $subscriber,
      $queue,
      $shortcodesPreview
    );
    $renderedNewsletter = $this->shortcodes->replace($newsletterBody);
    if ($replaceViewInBrowserUrlWithPublicShareUrl) {
      $renderedNewsletter = str_replace(
        $this->newsletterUrl->getViewInBrowserUrl($newsletter, null, $queue, true),
        $this->newsletterUrl->getPublicShareUrl($newsletter),
        $renderedNewsletter
      );
    }
    if ($replaceSubscriberTracking && $queue && $subscriber && $isTrackingEnabled) {
      $renderedNewsletter = $this->links->replaceSubscriberData(
        $subscriber->getId(),
        $queue->getId(),
        $renderedNewsletter
      );
    }
    if ($newsletter->getWpPostId() !== null) {
      $this->personalizer->set_context([
        'recipient_email' => $subscriber ? $subscriber->getEmail() : null,
        'is_user_preview' => $personalizerPreview,
        'newsletter_id' => $newsletter->getId(),
        'queue_id' => $queue ? $queue->getId() : null,
      ]);
      $renderedNewsletter = $this->personalizer->personalize_content($renderedNewsletter);
    }
    return $renderedNewsletter;
  }

  private function prepareShortcodes(
    NewsletterEntity $newsletter,
    ?SubscriberEntity $subscriber,
    ?SendingQueueEntity $queue,
    bool $wpUserPreview
  ) {
    $this->shortcodes->setQueue($queue);
    $this->shortcodes->setNewsletter($newsletter);
    $this->shortcodes->setWpUserPreview($wpUserPreview);
    $this->shortcodes->setSubscriber($subscriber);
  }
}
