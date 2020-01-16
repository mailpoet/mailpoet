<?php

namespace MailPoet\Subscription;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\WP\Functions as WPFunctions;

class Comment {
  const SPAM = 'spam';
  const APPROVED = 1;
  const PENDING_APPROVAL = 0;

  /** @var SettingsController */
  private $settings;

  /** @var SubscriberActions */
  private $subscriberActions;

  public function __construct(
    SettingsController $settings,
    SubscriberActions $subscriberActions
  ) {
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
  }

  public function extendLoggedInForm($field) {
    $field .= $this->getSubscriptionField();
    return $field;
  }

  public function extendLoggedOutForm() {
    echo $this->getSubscriptionField();
  }

  private function getSubscriptionField() {
    $label = $this->settings->get(
      'subscribe.on_comment.label',
      WPFunctions::get()->__('Yes, please add me to your mailing list.', 'mailpoet')
    );

    return '<p class="comment-form-mailpoet">
      <label for="mailpoet_subscribe_on_comment">
        <input
          type="checkbox"
          id="mailpoet_subscribe_on_comment"
          value="1"
          name="mailpoet[subscribe_on_comment]"
        />&nbsp;' . esc_attr($label) . '
      </label>
    </p>';
  }

  public function onSubmit($commentId, $commentStatus) {
    if ($commentStatus === Comment::SPAM) return;

    if (
      isset($_POST['mailpoet']['subscribe_on_comment'])
      && (bool)$_POST['mailpoet']['subscribe_on_comment'] === true
    ) {
      if ($commentStatus === Comment::PENDING_APPROVAL) {
        // add a comment meta to remember to subscribe the user
        // once the comment gets approved
        WPFunctions::get()->addCommentMeta(
          $commentId,
          'mailpoet',
          'subscribe_on_comment',
          true
        );
      } else if ($commentStatus === Comment::APPROVED) {
        $this->subscribeAuthorOfComment($commentId);
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
        $this->subscribeAuthorOfComment($commentId);

        WPFunctions::get()->deleteCommentMeta($commentId, 'mailpoet');
      }
    }
  }

  private function subscribeAuthorOfComment($commentId) {
    $segmentIds = $this->settings->get('subscribe.on_comment.segments', []);

    if (!empty($segmentIds)) {
      $comment = WPFunctions::get()->getComment($commentId);

      $result = $this->subscriberActions->subscribe(
        [
          'email' => $comment->comment_author_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          'first_name' => $comment->comment_author, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        ],
        $segmentIds
      );
    }
  }
}
