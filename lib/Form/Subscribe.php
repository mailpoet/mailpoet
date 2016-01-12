<?php
namespace MailPoet\Form;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Subscriber;

class Subscribe {
  static function inComments() {
    $label = (isset($subscribe_settings['on_comment']['label'])
      ? $subscribe_settings['on_comment']['label']
      : __('Yes, add me to your mailing list.')
    );

    $html = <<<EOL
      <p class="comment-form-mailpoet">
        <label for="mailpoet_subscribe_on_comment">
          <input
            type="checkbox"
            id="mailpoet_subscribe_on_comment"
            value="1"
            name="mailpoet[subscribe_on_comment]"
          />&nbsp;$label
        </label>
      </p>
EOL;
    echo $html;
  }

  static function onCommentSubmit($comment_id, $comment_approved) {
    if($comment_approved === 'spam') return;

    if(
      isset($_POST['mailpoet']['subscribe_on_comment'])
      &&
      filter_var(
        $_POST['mailpoet']['subscribe_on_comment'],
        FILTER_VALIDATE_BOOLEAN
      ) === true
    ) {
      if($comment_approved === 0) {
        add_comment_meta(
          $comment_id,
          'mailpoet',
          'subscribe_on_comment',
          true
        );
      } else {
        $subscribe_settings = Setting::getValue('subscribe');
        $segment_ids = (array)$subscribe_settings['on_comment']['segments'];

        if($subscribe_settings !== null) {
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
  }

  static function onCommentStatusUpdate($comment_id, $comment_status) {
    $comment_meta = get_comment_meta(
      $comment_id,
      'mailpoet',
      true
    );

    if(
      $comment_status ==='approve'
      && $comment_meta === 'subscribe_on_comment'
    ) {
      $subscribe_settings = Setting::getValue('subscribe');
      $segment_ids = (array)$subscribe_settings['on_comment']['segments'];

      if($subscribe_settings !== null) {
        $comment = get_comment($comment_id);

        Subscriber::subscribe(
          array(
            'email' => $comment->comment_author_email,
            'first_name' => $comment->comment_author
          ),
          $segment_ids
        );
      }
    }
  }
}