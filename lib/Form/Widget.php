<?php
namespace MailPoet\Form;
use \MailPoet\Config\Renderer;
use \MailPoet\Models\Form;
use \MailPoet\Models\Subscriber;
use \MailPoet\Form\Renderer as FormRenderer;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {

  function __construct () {
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

/*
add_action('wp_ajax_mailpoet_form_subscribe',     'mailpoet_form_subscribe');
add_action('wp_ajax_nopriv_mailpoet_form_subscribe',  'mailpoet_form_subscribe');
add_action('admin_post_nopriv_mailpoet_form_subscribe', 'mailpoet_form_subscribe');
add_action('admin_post_mailpoet_form_subscribe',    'mailpoet_form_subscribe');
add_action('init',                    'mailpoet_form_subscribe');


// set the content filter to replace the shortcode

if(isset($_GET['mailpoet_page']) && strlen(trim($_GET['mailpoet_page'])) > 0) {
  $mailpoet = new MailPoetWPI();

  switch($_GET['mailpoet_page']) {

    case 'mailpoet_form_iframe':
      $form_id = (isset($_GET['mailpoet_form']) && (int)$_GET['mailpoet_form'] > 0) ? (int)$_GET['mailpoet_form'] : null;
      $form = $mailpoet->forms()->fetchById($form_id);

      if($form !== null) {
        // render form
        print FormRenderer::getExport('html', $form);
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
  $mailpoet = new MailPoetWPI();
  // get signup confirmation page id
  $page_id = $mailpoet->settings()->get('signup_confirmation_page');

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

function mailpoet_signup_confirmation_email(array $subscriber, array $lists) {
  $mailpoet = new MailPoetWPI();

  $mailer = new MailPoetMailer($mailpoet->settings()->getAll());

  // email subject & body
  $email_subject = $mailpoet->settings()->get('signup_confirmation_email_subject');
  $email_body = nl2br($mailpoet->settings()->get('signup_confirmation_email_body'));

  // check for lists_to_confirm tag
  if(strpos($email_body, '[lists_to_confirm]') !== FALSE) {
    // gather all names from lists
    $list_names = array_map(function($list) { return $list['list_name']; }, $lists);
    // replace shortcode by list names in email's body
    $email_body = str_replace('[lists_to_confirm]', join(', ', $list_names), $email_body);
  }

  // check for activation_link tags
  if(strpos($email_body, '[activation_link]') !== FALSE && strpos($email_body, '[/activation_link]') !== FALSE) {
    // get confirmation page id
    $confirmation_page_id = $mailpoet->settings()->get('signup_confirmation_page');

    // generate confirmation link
    $confirmation_link = add_query_arg(array(
      'mailpoet_key' => $subscriber['subscriber_digest']
    ), get_permalink($confirmation_page_id));

    // we have both tags
    $email_body = str_replace(
      array('[activation_link]', '[/activation_link]'),
      array('<a href="'.$confirmation_link.'">', '</a>'),
      $email_body
    );
  } else {
    // no activation link tags detected
    // TODO...
  }

  // send confirmation email
  return $mailer->send(array(
    'from_email'  => $mailpoet->settings()->get('signup_confirmation_from_email'),
    'from_name'   => $mailpoet->settings()->get('signup_confirmation_from_name'),
    'reply_email' => $mailpoet->settings()->get('signup_confirmation_reply_email'),
    'reply_name'  => $mailpoet->settings()->get('signup_confirmation_reply_name'),
    'subject'   => $email_subject,
    'html'      => $email_body,
    'text'      => '',
    'to_email'    => $subscriber['subscriber_email'],
    'to_name'   => $subscriber['subscriber_email'],
  ));
}

function mailpoet_form_subscribe() {
  // check to see if we're in an ajax request or post request
  $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);

  if(isset($_GET['action']) && $_GET['action'] === 'mailpoet_form_subscribe') {

    // MailPoetWPI instance
    $mailpoet = new MailPoetWPI();

    // input data
    $data = array();

    // output errors
    $errors = array();

    // get posted data
    // ajax data
    $data = json_decode(file_get_contents('php://input'), true);
    // -or- post data
    if($data === NULL && !empty($_POST)) { $data = $_POST; }

    // define null subscriber
    $subscriber = null;

    // check if email is valid
    if(MailPoetMailer::isEmailAddress($data['email']) === false) {
      // email invalid
      $errors[] = __('Invalid email address.');
    } else {
      // search for subscriber in database
      $subscribers = $mailpoet->subscribers()->select(array(
        'filter' => array(
          'subscriber_email' => $data['email']
        ),
        'offset' => 0,
        'limit' => 1
      ));

      // check if we have any subscriber that matches
      if(count($subscribers) > 0) {
        // use existing subscriber
        $subscriber = $subscribers[0];
      }

      // is signup confirmation enabled?
      $needs_confirmation = (bool)$mailpoet->settings()->get('signup_confirmation');

      if($subscriber === null) {
        // create new subscriber
        $subscriber = array(
          'subscriber_email' => $data['email'],
          'subscriber_firstname' => (isset($data['subscriber_firstname'])) ? $data['subscriber_firstname'] : '',
          'subscriber_lastname' => (isset($data['subscriber_lastname'])) ? $data['subscriber_lastname'] : '',
          'subscriber_state' => ($needs_confirmation === true) ? MailPoetSubscribers::STATE_UNCONFIRMED : MailPoetSubscribers::STATE_SUBSCRIBED,
          'subscriber_created_at' => time()
        );

        // set custom fields
        $meta_fields = $mailpoet->getOption('mailpoet_subscriber_meta', array());
        if(!empty($meta_fields)) {
          // loop through data to see if any meta field has been passed
          foreach($meta_fields as $field => $field_data) {
            // check if it's a mandatory field
            $is_required = (isset($field_data['params']['required']) && (bool)$field_data['params']['required'] === true);

            if(array_key_exists($field, $data)) {
              // check if it's a mandatory field
              if($is_required === true && empty($data[$field])) {
                // if it's missing, throw an error
                $errors[] = sprintf(__('&quot;%s&quot; is required'), $field_data['name']);
              } else {
                // assign field to subscriber
                $subscriber[$field] = $data[$field];
              }
            }
          }
        }

        if(empty($errors)) {
          // insert new subscriber
          try {
            $subscriber = $mailpoet->subscribers()->insert($subscriber);
          } catch(Exception $e) {
            $errors[] = $e->getMessage();
          }
        }
      } else {
        // restore deleted subscriber
        if($subscriber['subscriber_deleted_at'] !== NULL) {
          // reset subscriber state (depends whether signup confirmation is enabled)
          if($needs_confirmation === true) {
            $subscriber['subscriber_state'] = MailPoetSubscribers::STATE_UNCONFIRMED;
          } else {
            $subscriber['subscriber_state'] = MailPoetSubscribers::STATE_SUBSCRIBED;
          }
          try {
            // update subscriber (reset deleted_at and state)
            $mailpoet->subscribers()->update(array(
              'subscriber' => $subscriber['subscriber'],
              'subscriber_state' => $subscriber['subscriber_state'],
              'subscriber_deleted_at' => NULL
            ));
          } catch(Exception $e) {
            $errors[] = __('An error occurred. Please try again later.');
          }
        }
      }
    }

    // check if form id has been passed
    if(isset($data['form']) && (int)$data['form'] > 0) {
      // get form id
      $form_id = (int)$data['form'];
      // get form
      $form = $mailpoet->forms()->fetchById($form_id);

      if($form === null) {
        $errors[] = __('This form does not exist. Please check your forms.');
      } else {
        // set subscriptions
        if(empty($data['lists'])) {
          $errors[] = __('You need to select a list');
        } else {
          // extract list ids
          $list_ids = array_map('intval', explode(',', $data['lists']));
          // get lists
          $lists = $mailpoet->lists()->fetchByIds($list_ids);

          // subscribe user to lists
          $has_subscribed = $mailpoet->subscriptions()->addSubscriberToLists($subscriber, $lists, $form);

          // if signup confirmation is enabled and the subscriber is unconfirmed
          if( $needs_confirmation === true
            && $has_subscribed === true
            && !empty($lists)
            && !empty($subscriber['subscriber_digest'])
            && $subscriber['subscriber_state'] !== MailPoetSubscribers::STATE_SUBSCRIBED
          ) {
            // resend confirmation email
            $is_sent = mailpoet_signup_confirmation_email($subscriber, $lists);

            // error message if the email could not be sent
            if($is_sent === false) {
              $errors[] = __('The signup confirmation email could not be sent. Please check your settings.');
            }
          }
        }
      }

      // get success message to display after subscription
      $form_settings = (isset($form['settings']) ? $form['settings'] : null);

      if($subscriber !== null && empty($errors)) {
        $success = true;
        $message = $form_settings['success_message'];
      } else {
        $success = false;
        $message = join('<br />', $errors);
      }

      if($form_settings !== null) {

        // url params for non ajax requests
        if($doing_ajax === false) {
          // get referer
          $referer = (wp_get_referer() !== false) ? wp_get_referer() : $_SERVER['HTTP_REFERER'];

          // redirection parameters
          $params = array(
            'mailpoet_form' => (int)$data['form']
          );

          // handle success/error messages
          if($success === false) {
            $params['mailpoet_error'] = urlencode($message);
          } else {
            $params['mailpoet_success'] = urlencode($message);
          }
        }

        switch ($form_settings['on_success']) {
          case 'page':
            // response depending on context
            if($doing_ajax === true) {
              echo json_encode(array(
                'success' => $success,
                'page' => get_permalink($form_settings['success_page']),
                'message' => $message
              ));
            } else {
              $redirect_to = ($success === false) ? $referer : get_permalink($form_settings['success_page']);
              wp_redirect(add_query_arg($params, $redirect_to));
            }
          break;

          case 'message':
          default:
            // response depending on context
            if($doing_ajax === true) {
              echo json_encode(array(
                'success' => $success,
                'message' => $message
              ));
            } else {
              // redirect to previous page
              wp_redirect(add_query_arg($params, $referer));
            }
          break;
        }
      }
    }
    exit();
  }
}
*/