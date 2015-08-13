<?php
namespace MailPoet\Form;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {

  function __construct() {
    return parent::__construct(
      'mailpoet_form',
      __('MailPoet Subscription Form'),
      array(
        'title' => __('Newsletter subscription form'),
      )
    );
  }

  public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['form'] = (int)$new_instance['form'];
    return $instance;
  }

  public function form($instance) {
    $instance = wp_parse_args(
      (array)$instance,
      array(
        'title' => __('Subscribe to our Newsletter')
      )
    );

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';
    ?>
    <p>
      <label for="<?php $this->get_field_id( 'title' ) ?>">
        <?php _e( 'Title:' ); ?>
      </label>
      <input
        type="text"
        class="widefat"
        id="<?php echo $this->get_field_id('title') ?>"
        name="<?php echo $this->get_field_name('title'); ?>"
        value="<?php echo esc_attr($title); ?>"
      />
    </p>
    <p>
      <a href="javascript:;" class="mailpoet_form_new">
        <?php _e("Create a new form"); ?>
      </a>
    </p>
    <?php
  }

  function widget($args, $instance = null) {
    extract($args);
    if($instance === null) { $instance = $args; }

    $title = apply_filters(
      'widget_title',
      $instance['title'],
      $instance,
      $this->id_base
    );

    $output = '';

    // before widget
    $output .= (isset($before_widget) ? $before_widget : '');

    // title
    $output .= (isset($before_title) ? $before_title : '');
    $output .= (isset($title) ? $title : '');
    $output .= (isset($after_title) ? $after_title : '');

    // form
    $form_id = $this->id_base.'_'.$this->number;
    $form_type = 'widget';
    $output .= '<div class="mailpoet_form mailpoet_form_'.$form_type.'">';

    $output .= '<form '.
      'id="'.$form_id.'" '.
      'method="post" '.
      'action="'.admin_url('admin-post.php?action=mailpoet_form_subscribe').'" '.
      'class="mailpoet_form mailpoet_form_'.$form_type.'" novalidate>';

    $output .= '  <p>';
    $output .= '    <label>';
    $output .=        __('E-mail').' <input type="email" name="email" data-validation-engine="validate[required,custom[email]]"/>';
    $output .= '    </label>';
    $output .= '  </p>';

    $output .= '  <p>';
    $output .= '    <label>';
    $output .= '      <input type="submit" value="'.esc_attr('Subscribe!').'" />';
    $output .= '    </label>';
    $output .= '  </p>';

    $output .= '</form>';
    $output .= '</div>';

    // after widget
    $output .= (isset($after_widget) ? $after_widget : '');

    echo $output;
  }
}