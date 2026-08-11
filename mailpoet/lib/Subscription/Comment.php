<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscribers\TrackingConsentCapture;
use MailPoet\WP\Functions as WPFunctions;

class Comment {
  const SPAM = 'spam';
  const APPROVED = 1;
  const PENDING_APPROVAL = 0;

  /**
   * Comment meta remembering the tracking-consent choice while a comment waits
   * for moderation. The subscribe is deferred to approval, which happens in a
   * later request with no POST data, so the choice has to be stored with the
   * comment rather than re-read.
   */
  const TRACKING_CONSENT_META = 'mailpoet_tracking_consent';

  /** @var SettingsController */
  private $settings;

  /** @var SubscriberActions */
  private $subscriberActions;

  /** @var TrackingConsentCapture */
  private $trackingConsentCapture;

  public function __construct(
    SettingsController $settings,
    SubscriberActions $subscriberActions,
    TrackingConsentCapture $trackingConsentCapture
  ) {
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
    $this->trackingConsentCapture = $trackingConsentCapture;
  }

  public function extendLoggedInForm($field) {
    $field .= $this->getSubscriptionField();
    return $field;
  }

  public function extendLoggedOutForm() {
    // The method returns escaped content
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->getSubscriptionField();
  }

  /**
   * Returns escaped HTML for the subscription field.
   *
   * @return string
   */
  private function getSubscriptionField(): string {
    $label = $this->settings->get(
      'subscribe.on_comment.label',
      __('Yes, please add me to your mailing list.', 'mailpoet')
    );

    return '<p class="comment-form-mailpoet">
      <label for="mailpoet_subscribe_on_comment">
        <input
          type="checkbox"
          id="mailpoet_subscribe_on_comment"
          value="1"
          name="mailpoet[subscribe_on_comment]"
        />&nbsp;' . esc_html($label) . '
      </label>
    </p>' . $this->getTrackingConsentField();
  }

  /**
   * A second, independent checkbox. Consent to open and click tracking is never
   * inferred from the subscribe box above it, and it is only shown on sites
   * that chose to ask. Never pre-ticked: a pre-ticked consent box is not valid
   * consent (CJEU Planet49).
   */
  private function getTrackingConsentField(): string {
    if (!$this->trackingConsentCapture->isCaptureEnabled()) {
      return '';
    }
    $copy = $this->trackingConsentCapture->getCopy(
      SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT
    );

    return '<p class="comment-form-mailpoet-tracking-consent">
      <label for="mailpoet_tracking_consent">
        <input
          type="checkbox"
          id="mailpoet_tracking_consent"
          value="1"
          name="mailpoet[tracking_consent]"
        />&nbsp;' . esc_html($copy) . '
      </label>
    </p>';
  }

  public function onSubmit($commentId, $commentStatus) {
    if ($commentStatus === Comment::SPAM) return;

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- type narrowing only, value is read as bool
    $mailpoetPost = isset($_POST['mailpoet']) && is_array($_POST['mailpoet']) ? $_POST['mailpoet'] : [];
    if (
      isset($mailpoetPost['subscribe_on_comment'])
      && (bool)$mailpoetPost['subscribe_on_comment'] === true
    ) {
      $trackingConsent = !empty($mailpoetPost['tracking_consent']);
      if ($commentStatus === Comment::PENDING_APPROVAL) {
        // add a comment meta to remember to subscribe the user
        // once the comment gets approved
        WPFunctions::get()->addCommentMeta(
          $commentId,
          'mailpoet',
          'subscribe_on_comment',
          true
        );
        // Approval runs in a later request with no POST data, so the consent
        // choice is stored alongside it rather than re-read then.
        WPFunctions::get()->addCommentMeta(
          $commentId,
          self::TRACKING_CONSENT_META,
          $trackingConsent ? '1' : '0',
          true
        );
      } elseif ($commentStatus === Comment::APPROVED) {
        $this->subscribeAuthorOfComment($commentId, $trackingConsent);
      }
    }
  }

  public function onStatusUpdate($commentId, $action) {
    if ($action === 'approve') {
      // check if the comment's author wants to subscribe
      $doSubscribe = (
        WPFunctions::get()->getCommentMeta(
          $commentId,
          'mailpoet',
          true
        ) === 'subscribe_on_comment'
      );

      if ($doSubscribe === true) {
        $trackingConsent = WPFunctions::get()->getCommentMeta(
          $commentId,
          self::TRACKING_CONSENT_META,
          true
        ) === '1';
        $this->subscribeAuthorOfComment($commentId, $trackingConsent);

        WPFunctions::get()->deleteCommentMeta($commentId, 'mailpoet');
        WPFunctions::get()->deleteCommentMeta($commentId, self::TRACKING_CONSENT_META);
      }
    }
  }

  private function subscribeAuthorOfComment($commentId, bool $trackingConsent = false) {
    $segmentIds = $this->settings->get('subscribe.on_comment.segments', []);

    if (!empty($segmentIds)) {
      $comment = WPFunctions::get()->getComment($commentId);
      $email = $comment->comment_author_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $method = SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT;
      $consentData = $this->trackingConsentCapture->getConsentData(
        $trackingConsent,
        $method,
        $this->trackingConsentCapture->getCopy($method),
        $this->trackingConsentCapture->isNewSubscriber($email)
      );

      $this->subscriberActions->subscribe(
        array_merge([
          'email' => $email,
          'first_name' => $comment->comment_author, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        ], $consentData),
        $segmentIds
      );
    }
  }
}
