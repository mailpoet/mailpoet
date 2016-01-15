<?php
namespace MailPoet\Form;
use \MailPoet\Config\Renderer;
use \MailPoet\Models\Form;
use \MailPoet\Models\Segment;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Subscriber;
use \MailPoet\Form\Renderer as FormRenderer;
use \MailPoet\Form\Util;

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
      __('MailPoet Form'),
      array(
        'description' => __('Add a newsletter subscription form.')
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
    $forms = Form::getPublished()->orderByAsc('name')->findArray();
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
      <a href="javascript:;" onClick="createSubscriptionForm()" class="mailpoet_form_new"><?php _e("Create a new form"); ?></a>
    </p>
    <script type="text/javascript">
    function createSubscriptionForm() {
        MailPoet.Ajax.post({
          endpoint: 'forms',
          action: 'create'
        }).done(function(response) {
          if(response !== false) {
            window.location = response;
          }
        });
        return false;
    }
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
    $form = Form::getPublished()->findOne($instance['form']);

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
          'before_widget' => (!empty($before_widget) ? $before_widget : ''),
          'after_widget' => (!empty($after_widget) ? $after_widget : ''),
          'before_title' => (!empty($before_title) ? $before_title : ''),
          'after_title' => (!empty($after_title) ? $after_title : '')
        );

        // if(isset($_GET['mailpoet_form']) && (int)$_GET['mailpoet_form'] === $form['id']) {
        //   // form messages (success / error)
        //   $output .= '<div class="mailpoet_message">';
        //   // success message
        //   if(isset($_GET['mailpoet_success'])) {
        //     $output .= '<p class="mailpoet_validate_success">'.strip_tags(urldecode($_GET['mailpoet_success']), '<a><strong><em><br><p>').'</p>';
        //   }
        //   // error message
        //   if(isset($_GET['mailpoet_error'])) {
        //     $output .= '<p class="mailpoet_validate_error">'.strip_tags(urldecode($_GET['mailpoet_error']), '<a><strong><em><br><p>').'</p>';
        //   }
        //   $output .= '</div>';
        // } else {
        //   $output .= '<div class="mailpoet_message"></div>';
        // }

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