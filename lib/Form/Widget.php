<?php
namespace MailPoet\Form;
use \MailPoet\Config\Renderer;
use \MailPoet\Models\Form;
use \MailPoet\Models\Segment;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Subscriber;
use \MailPoet\Form\Renderer as FormRenderer;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {
  function __construct () {
    // add_action(
    //   'wp_ajax_mailpoet_form_subscribe',
    //   array($this, 'subscribe')
    // );
    // add_action(
    //   'wp_ajax_nopriv_mailpoet_form_subscribe',
    //   array($this, 'subscribe')
    // );
    // add_action(
    //   'admin_post_nopriv_mailpoet_form_subscribe',
    //   array($this, 'subscribe')
    // );
    // add_action(
    //   'admin_post_mailpoet_form_subscribe',
    //   array($this, 'subscribe')
    // );

    // add_action(
    //   'init',
    //   array($this, 'subscribe')
    // );

    return parent::__construct(
      'mailpoet_form',
      __("MailPoet Subscription Form"),
      array(
        'title' => __("Newsletter subscription form"),
      )
    );
  }

  /**
   * Save the new widget's title.
   */
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['form'] = (int)$new_instance['form'];
    return $instance;
  }

  /**
   * Output the widget's option form.
   */
  public function form($instance) {

    $instance = wp_parse_args(
      (array)$instance,
      array(
        'title' => __("Subscribe to our Newsletter")
      )
    );

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';

    // set form
    $selected_form = isset($instance['form']) ? (int)($instance['form']) : 0;

    // get forms list
    $forms = Form::whereNull('deleted_at')->orderByAsc('name')->findArray();
    ?><p>
      <label for="<?php $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:' ); ?></label>
      <input
        type="text"
        class="widefat"
        id="<?php echo $this->get_field_id('title') ?>"
        name="<?php echo $this->get_field_name('title'); ?>"
        value="<?php echo esc_attr($title); ?>"
      />
    </p>
    <p>
      <select class="widefat" id="<?php echo $this->get_field_id('form') ?>" name="<?php echo $this->get_field_name('form'); ?>">
        <?php
        foreach ($forms as $form) {
          $is_selected = ($selected_form === (int)$form['id']) ? 'selected="selected"' : '';
        ?>
        <option value="<?php echo (int)$form['id']; ?>" <?php echo $is_selected; ?>><?php echo esc_html($form['name']); ?></option>
        <?php }  ?>
      </select>
    </p>
    <p>
      <a href="javascript:;" class="mailpoet_form_new"><?php _e("Create a new form"); ?></a>
    </p>
    <script type="text/javascript">
    jQuery(function($) {
      $(function() {
        $('.mailpoet_form_new').on('click', function() {
          MailPoet.Ajax.post({
            endpoint: 'forms',
            action: 'create'
          }).done(function(response) {
            if(response !== false) {
              window.location = response;
            }
          });
        });
      });
    });
    </script>
    <?php
  }

  /**
   * Output the widget itself.
   */
  function widget($args, $instance = null) {
    // turn $args into variables
        extract($args);

        if($instance === null) {
          $instance = $args;
        }

    $title = apply_filters(
      'widget_title',
      !empty($instance['title']) ? $instance['title'] : '',
      $instance,
      $this->id_base
    );

    // get form
    $form = Form::whereNull('deleted_at')->findOne($instance['form']);

    // if the form was not found, return nothing.
    if($form === false) {
      return '';
    } else {
      $form = $form->asArray();
      $form_type = 'widget';
      if(isset($instance['form_type']) && in_array(
        $instance['form_type'],
        array('html', 'php', 'iframe', 'shortcode')
      )) {
        $form_type = $instance['form_type'];
      }

      $settings = (isset($form['settings']) ? $form['settings'] : array());
      $body = (isset($form['body']) ? $form['body'] : array());
      $output = '';

      if(!empty($body)) {
        $data = array(
          'form_id' => $this->id_base.'_'.$this->number,
          'form_type' => $form_type,
          'form' => $form,
          'title' => $title,
          'styles' => FormRenderer::renderStyles($form),
          'html' => FormRenderer::renderHTML($form),
          'before_widget' => $before_widget,
          'after_widget' => $after_widget,
          'before_title' => $before_title,
          'after_title' => $after_title
        );

        /*if(isset($_GET['mailpoet_form']) && (int)$_GET['mailpoet_form'] === $form['id']) {
          // form messages (success / error)
          $output .= '<div class="mailpoet_message">';
          // success message
          if(isset($_GET['mailpoet_success'])) {
            $output .= '<p class="mailpoet_validate_success">'.strip_tags(urldecode($_GET['mailpoet_success']), '<a><strong><em><br><p>').'</p>';
          }
          // error message
          if(isset($_GET['mailpoet_error'])) {
            $output .= '<p class="mailpoet_validate_error">'.strip_tags(urldecode($_GET['mailpoet_error']), '<a><strong><em><br><p>').'</p>';
          }
          $output .= '</div>';
        } else {
          $output .= '<div class="mailpoet_message"></div>';
        }*/

        // render form
        $renderer = new Renderer();
        $renderer = $renderer->init();
        $output = $renderer->render('form/widget.html', $data);
        $output = do_shortcode($output);
      }

      if($form_type === 'widget') {
        echo $output;
      } else {
        return $output;
      }
    }
  }
}

// mailpoet shortcodes
// form shortcode
add_shortcode('mailpoet_form',  'mailpoet_form_shortcode');
add_shortcode('wysija_form',  'mailpoet_form_shortcode');

function mailpoet_form_shortcode($params = array()) {
  // IMPORTANT: this is to make sure MagicMember won't scan our form and find [user_list] as a code they should replace.
    remove_shortcode('user_list');

    if(isset($params['id']) && (int)$params['id'] > 0) {
        $form_widget = new \MailPoet\Form\Widget();
    return $form_widget->widget(array(
      'form' => (int)$params['id'],
      'form_type' => 'shortcode'
    ));
    }
}

// set the content filter to replace the shortcode
if(isset($_GET['mailpoet_page']) && strlen(trim($_GET['mailpoet_page'])) > 0) {
  switch($_GET['mailpoet_page']) {

    case 'mailpoet_form_iframe':
      $id = (isset($_GET['mailpoet_form']) && (int)$_GET['mailpoet_form'] > 0) ? (int)$_GET['mailpoet_form'] : null;
      $form = Form::findOne($id);

      if($form !== false) {
        // render form
        print FormRenderer::getExport('html', $form->asArray());
        exit;
      }
    break;

    default:
      // add_filter('wp_title', 'mailpoet_meta_page_title'));
      add_filter('the_title',   'mailpoet_page_title',    10, 2);
      add_filter('the_content', 'mailpoet_page_content',  98, 1);
    break;
  }
}

function mailpoet_page_title($title = '', $id = null) {
  // get signup confirmation page id
  $signup_confirmation = Setting::getValue('signup_confirmation');
  $page_id = $signup_confirmation['page'];

  // check if we're on the signup confirmation page
  if((int)$page_id === (int)$id) {
    global $post;

    // disable comments
    $post->comment_status = 'close';
    // disable password
    $post->post_password = '';

    $subscriber = null;

    // get subscriber key from url
    $subscriber_digest = (isset($_GET['mailpoet_key']) && strlen(trim($_GET['mailpoet_key'])) === 32) ? trim($_GET['mailpoet_key']) : null;

    if($subscriber_digest !== null) {
      // get subscriber
      // TODO: change select() to selectOne() once it's implemented
      $subscribers = $mailpoet->subscribers()->select(array(
        'filter' => array(
          'subscriber_digest' => $subscriber_digest
        ),
        'limit' => 1
      ));

      if(!empty($subscribers)) {
        $subscriber = array_shift($subscribers);
      }
    }

    // check if we have a subscriber record
    if($subscriber === null) {
      return __('Your confirmation link expired, please subscribe again.');
    } else {
      // we have a subscriber, let's check its state
      switch($subscriber['subscriber_state']) {
        case MailPoetSubscribers::STATE_UNCONFIRMED:
        case MailPoetSubscribers::STATE_UNSUBSCRIBED:
          // set subscriber state as confirmed
          $mailpoet->subscribers()->update(array(
            'subscriber' => $subscriber['subscriber'],
            'subscriber_state' => MailPoetSubscribers::STATE_SUBSCRIBED,
            'subscriber_confirmed_at' => time()
          ));
          return __("You've subscribed");
        break;
        case MailPoetSubscribers::STATE_SUBSCRIBED:
          return __("You've already subscribed");
        break;
      }
    }
  } else {
    return $title;
  }
}

function mailpoet_page_content($content = '') {
  if(strpos($content, '[mailpoet_page]') !== FALSE) {
    $content = str_replace('[mailpoet_page]', '', $content);
  }
  return $content;
}