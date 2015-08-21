<?php
namespace MailPoet\Form;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {

  function __construct() {
    return parent::__construct(
      'mailpoet_form',
      __('MailPoet Subscription Form'),
      array(
        'title' => __('Newsletter subscription form')
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

    $output = '';

    $output .= '<p>';
    $output .= '  <label for="'.$this->get_field_id('title').'">';
    $output .= __('Title:' );
    $output .= '  </label>';
    $output .= '  <input type="text" class="widefat"';
    $output .= '    id="'.$this->get_field_id('title').'"';
    $output .= '    name="'.$this->get_field_name('title').'"';
    $output .= '    value="'.esc_attr($title).'"';
    $output .= '  />';
    $output .= '</p>';
    $output .= '<p>';
    $output .= '  <a href="javascript:;" class="mailpoet_form_new">';
    $output .= __('Create a new form');
    $output .= '  </a>';
    $output .= '</p>';

    echo $output;
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

    $form_id = $this->id_base.'_'.$this->number;
    $form_type = 'widget';

    $output = '';

    // before widget
    $output .= (isset($before_widget) ? $before_widget : '');

    // title
    $output .= $before_title.$title.$after_title;

    // container
    $output .= '<div class="mailpoet_form mailpoet_form_'.$form_type.'">';

    // styles
    $styles = '.mailpoet_validate_success { color:#468847; }';
    $styles .= '.mailpoet_validate_error { color:#B94A48; }';
    $output .= '<style type="text/css">'.$styles.'</style>';

    $output .= '<form '.
      'id="'.$form_id.'" '.
      'method="post" '.
      'action="'.admin_url('admin-post.php?action=mailpoet_form_subscribe').'" '.
      'class="mailpoet_form mailpoet_form_'.$form_type.'" novalidate>';

    $output .= '<div class="mailpoet_message"></div>';

    $output .= '  <p>';
    $output .= '    <label>'.__('E-mail');
    $output .= '      <input type="email" name="email"';
    $output .= '      data-validation-engine="validate[required,custom[email]]"';
    $output .= '      data-msg="'.__('This field is required.').'"';
    $output .= '      required';
    $output .= '      />';
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