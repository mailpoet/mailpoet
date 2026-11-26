<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\ReviewStatus;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class PendingApprovalNotice {

  const OPTION_NAME = 'mailpoet-pending-approval-notice';

  private const APPROVAL_PROCESS_URL = 'https://kb.mailpoet.com/article/379-our-approval-process';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function init($shouldDisplay): ?string {
    // We should display the notice if the user is using MSS and the subscription is not approved
    if (
      $shouldDisplay
      && $this->settings->get('mta.method') === Mailer::METHOD_MAILPOET
      && $this->settings->get('mta.mailpoet_api_key_state')
      && $this->settings->get('mta.mailpoet_api_key_state.state', null) === Bridge::KEY_VALID
      && !$this->settings->get('mta.mailpoet_api_key_state.data.is_approved', false)
    ) {
      $review = ReviewStatus::fromArray($this->wp->getTransient(Bridge::REVIEW_STATUS_TRANSIENT_NAME));
      return $review ? $this->displayReviewStatus($review) : $this->display();
    }

    return null;
  }

  public function getReviewStatusMessage(ReviewStatus $review): string {
    $since = $review->getSince();
    $date = $since ? $this->wp->wpDate($this->wp->getOption('date_format'), $since->getTimestamp()) : null;
    $actionRequired = $this->isActionRequired($review);

    if ($review->getStatus() === ReviewStatus::STATUS_FIX) {
      $text = sprintf(
        '%s %s',
        $this->getFixReasonText((string)$review->getReason()),
        // translators: %s is the date of our email.
        sprintf(__('We emailed you about this on %s. Reply to that email when you’re done, and we’ll continue the review.', 'mailpoet'), $date)
      );
    } elseif ($review->getStatus() === ReviewStatus::STATUS_QUESTION) {
      // translators: %s is the date of our email.
      $text = sprintf(__('We emailed you a question about your plan on %s. Reply to that email, and we’ll continue the review.', 'mailpoet'), $date);
    } elseif (!$date) {
      $text = '';
    } elseif ($review->getStatus() === ReviewStatus::STATUS_REPLIED) {
      // translators: %s is the date of the customer's reply.
      $text = sprintf(__('We got your reply on %s. We’ll email you when we’re done.', 'mailpoet'), $date);
    } else {
      // translators: %s is the date the review started.
      $text = sprintf(__('We started on %s. We’ll email you when we’re done.', 'mailpoet'), $date);
    }

    $link = sprintf('<a href="%s" target="_blank" rel="noreferrer">%%s</a>', self::APPROVAL_PROCESS_URL);
    $heading = $actionRequired ? __('Action required', 'mailpoet') : sprintf($link, __('We’re reviewing your plan', 'mailpoet'));
    $whyLink = $actionRequired ? ' ' . sprintf($link, __('Why is that?', 'mailpoet')) : '';

    // The data attributes are read by the analytics event for the shown status.
    return sprintf(
      '<strong data-mailpoet-review-status="%s" data-reason="%s" data-since="%s" data-public-subscription-id="%s">%s</strong> %s%s<br>%s',
      esc_attr($review->getStatus()),
      esc_attr((string)$review->getReason()),
      // Same format as the account page sends: the review dates are in UTC.
      esc_attr($since ? $since->format('Y-m-d\TH:i:s.v\Z') : ''),
      esc_attr((string)$this->settings->get('mta.mailpoet_api_key_state.data.public_subscription_id', null)),
      $heading,
      $text,
      $whyLink,
      $this->replaceBodyLinks(__('You can use all MailPoet features and send [link1]email previews[/link1] to your [link2]authorized email addresses[/link2], but sending to your email list contacts is temporarily paused until we review your subscription.', 'mailpoet'))
    );
  }

  private function isActionRequired(ReviewStatus $review): bool {
    return in_array($review->getStatus(), [ReviewStatus::STATUS_FIX, ReviewStatus::STATUS_QUESTION], true);
  }

  private function getFixReasonText(string $reason): string {
    switch ($reason) {
      case 'generic_content':
        return __('Your website looks unfinished or shows placeholder content. Add your real content, or reply with details about your business, like a social media page or your address.', 'mailpoet');
      case 'never_emailed_list':
        return __('Your list has never received an email from you. Ask your subscribers to confirm their subscription before you send with us.', 'mailpoet');
      case 'customers_invite_once':
        return __('Your customers haven’t agreed to receive marketing emails. Send your one-time invitation with another sending method, then import only the customers who accept.', 'mailpoet');
      default:
        // http_error and unreachable_website
        return __('We couldn’t open your website. Make sure it’s online and visible to visitors.', 'mailpoet');
    }
  }

  private function displayReviewStatus(ReviewStatus $review): string {
    $message = $this->getReviewStatusMessage($review);
    if ($this->isActionRequired($review)) {
      WPNotice::displayError($message, '', self::OPTION_NAME, true, false);
    } else {
      WPNotice::displayWarning($message, '', self::OPTION_NAME);
    }
    return $message;
  }

  public function getPendingApprovalTitle(): string {
    $message = __("MailPoet is [link]reviewing your subscription[/link].", 'mailpoet');
    return Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/379-our-approval-process',
      [
        'target' => '_blank',
        'rel' => 'noreferrer',
      ],
      'link'
    );
  }

  public function getPendingApprovalBody(): string {
    // translators: %s is the email subject, which will always be in English
    $message = sprintf(__("You can use all MailPoet features and send [link1]email previews[/link1] to your [link2]authorized email addresses[/link2], but sending to your email list contacts is temporarily paused until we review your subscription. If you don't hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email for follow-up emails with the subject \"%s\" and reply, or [link3]contact us[/link3].", 'mailpoet'), 'Your MailPoet Subscription Review');
    $message = $this->replaceBodyLinks($message);
    return Helpers::replaceLinkTags(
      $message,
      'https://www.mailpoet.com/support/',
      [
        'target' => '_blank',
        'rel' => 'noreferrer',
      ],
      'link3'
    );
  }

  private function replaceBodyLinks(string $message): string {
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/290-check-your-newsletter-before-sending-it',
      [
        'target' => '_blank',
        'rel' => 'noreferrer',
      ],
      'link1'
    );
    return Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/266-how-to-add-an-authorized-email-address-as-the-from-address#how-to-authorize-an-email-address',
      [
        'target' => '_blank',
        'rel' => 'noreferrer',
      ],
      'link2'
    );
  }

  public function getPendingApprovalMessage(): string {
    return sprintf('%s %s', $this->getPendingApprovalTitle(), $this->getPendingApprovalBody());
  }

  private function display(): string {
    $message = $this->getPendingApprovalMessage();
    WPNotice::displayWarning($message, '', self::OPTION_NAME);
    return $message;
  }
}
