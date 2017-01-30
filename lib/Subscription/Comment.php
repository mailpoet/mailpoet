<?php
namespace MailPoet\Subscription;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

class Comment {
  const SPAM = 'spam';
  const APPROVED = 1;
  const PENDING_APPROVAL = 0;

  static function extendLoggedInForm($field) {
    $field .= self::getSubscriptionField();
    return $field;
  }

  static function extendLoggedOutForm() {
    echo self::getSubscriptionField();
  }

  static function getSubscriptionField() {
    $label = Setting::getValue(
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
        />&nbsp;'.esc_attr($label).'
      </label>
    </p>';
  }

  static function onSubmit($comment_id, $comment_status) {
    if($comment_status === Comment::SPAM) return;

    if(
      isset($_POST['mailpoet']['subscribe_on_comment'])
      && (bool)$_POST['mailpoet']['subscribe_on_comment'] === true
    ) {
      if($comment_status === Comment::PENDING_APPROVAL) {
        // add a comment meta to remember to subscribe the user
        // once the comment gets approved
        add_comment_meta(
          $comment_id,
          'mailpoet',
          'subscribe_on_comment',
          true
        );
      } else if($comment_status === Comment::APPROVED) {
        static::subscribeAuthorOfComment($comment_id);
      }
    }
  }

  static function onStatusUpdate($comment_id, $action) {
    if($action === 'approve') {
      // check if the comment's author wants to subscribe
      $do_subscribe = (
        get_comment_meta(
          $comment_id,
          'mailpoet',
          true
        ) === 'subscribe_on_comment'
      );

      if($do_subscribe === true) {
        static::subscribeAuthorOfComment($comment_id);

        delete_comment_meta($comment_id, 'mailpoet');
      }
    }
  }

  private static function subscribeAuthorOfComment($comment_id) {
    $segment_ids = Setting::getValue('subscribe.on_comment.segments', array());

    if(!empty($segment_ids)) {
      $comment = get_comment($comment_id);

      $result = Subscriber::subscribe(
        array(
          'email' => $comment->comment_author_email,
          'first_name' => $comment->comment_author
        ),
        $segment_ids
      );
    }
  }
}
