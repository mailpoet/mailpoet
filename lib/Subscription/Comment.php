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
  private $subscriber_actions;

  function __construct(
    SettingsController $settings,
    SubscriberActions $subscriber_actions
  ) {
    $this->settings = $settings;
    $this->subscriber_actions = $subscriber_actions;
  }

  function extendLoggedInForm($field) {
    $field .= $this->getSubscriptionField();
    return $field;
  }

  function extendLoggedOutForm() {
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

  function onSubmit($comment_id, $comment_status) {
    if ($comment_status === Comment::SPAM) return;

    if (
      isset($_POST['mailpoet']['subscribe_on_comment'])
      && (bool)$_POST['mailpoet']['subscribe_on_comment'] === true
    ) {
      if ($comment_status === Comment::PENDING_APPROVAL) {
        // add a comment meta to remember to subscribe the user
        // once the comment gets approved
        WPFunctions::get()->addCommentMeta(
          $comment_id,
          'mailpoet',
          'subscribe_on_comment',
          true
        );
      } else if ($comment_status === Comment::APPROVED) {
        $this->subscribeAuthorOfComment($comment_id);
      }
    }
  }

  function onStatusUpdate($comment_id, $action) {
    if ($action === 'approve') {
      // check if the comment's author wants to subscribe
      $do_subscribe = (
        WPFunctions::get()->getCommentMeta(
          $comment_id,
          'mailpoet',
          true
        ) === 'subscribe_on_comment'
      );

      if ($do_subscribe === true) {
        $this->subscribeAuthorOfComment($comment_id);

        WPFunctions::get()->deleteCommentMeta($comment_id, 'mailpoet');
      }
    }
  }

  private function subscribeAuthorOfComment($comment_id) {
    $segment_ids = $this->settings->get('subscribe.on_comment.segments', []);

    if (!empty($segment_ids)) {
      $comment = WPFunctions::get()->getComment($comment_id);

      $result = $this->subscriber_actions->subscribe(
        [
          'email' => $comment->comment_author_email,
          'first_name' => $comment->comment_author,
        ],
        $segment_ids
      );
    }
  }
}
