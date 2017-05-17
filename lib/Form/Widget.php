<?php
namespace MailPoet\Form;
use MailPoet\API\JSON\API;
use MailPoet\Config\Renderer;
use MailPoet\Models\Form;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {
  function __construct () {
    return parent::__construct(
      'mailpoet_form',
      __('MailPoet Form', 'mailpoet'),
      array(
        'description' => __('Add a newsletter subscription form', 'mailpoet')
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
        'title' => __("Subscribe to Our Newsletter", 'mailpoet')
      )
    );

    $form_edit_url = admin_url('admin.php?page=mailpoet-form-editor&id=');

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';

    // set form
    $selected_form = isset($instance['form']) ? (int)($instance['form']) : 0;

    // get forms list
    $forms = Form::getPublished()->orderByAsc('name')->findArray();
    ?><p>
      <label for="<?php $this->get_field_id( 'title' ) ?>"><?php _e('Title:', 'mailpoet'); ?></label>
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
        foreach($forms as $form) {
          $is_selected = ($selected_form === (int)$form['id']) ? 'selected="selected"' : '';
        ?>
        <option value="<?php echo (int)$form['id']; ?>" <?php echo $is_selected; ?>><?php echo esc_html($form['name']); ?></option>
        <?php }  ?>
      </select>
    </p>
    <p>
      <a href="javascript:;" onClick="createSubscriptionForm()" class="mailpoet_form_new"><?php _e('Create a new form', 'mailpoet'); ?></a>
    </p>
    <script type="text/javascript">
    function createSubscriptionForm() {
        MailPoet.Ajax.post({
          endpoint: 'forms',
          action: 'create'
        }).done(function(response) {
          if(response.data && response.data.id) {
            window.location =
              "<?php echo $form_edit_url; ?>" + response.data.id;
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
        $form_id = $this->id_base.'_'.$form['id'];

        $data = array(
          'form_id' => $form_id,
          'form_type' => $form_type,
          'form' => $form,
          'title' => $title,
          'styles' => FormRenderer::renderStyles($form, '#'.$form_id),
          'html' => FormRenderer::renderHTML($form),
          'before_widget' => (!empty($before_widget) ? $before_widget : ''),
          'after_widget' => (!empty($after_widget) ? $after_widget : ''),
          'before_title' => (!empty($before_title) ? $before_title : ''),
          'after_title' => (!empty($after_title) ? $after_title : '')
        );

        // (POST) non ajax success/error variables
        $data['success'] = (
          (isset($_GET['mailpoet_success']))
          &&
          ((int)$_GET['mailpoet_success'] === (int)$form['id'])
        );
        $data['error'] = (
          (isset($_GET['mailpoet_error']))
          &&
          ((int)$_GET['mailpoet_error'] === (int)$form['id'])
        );

        // generate security token
        $data['token'] = Security::generateToken();

        // add API version
        $data['api_version'] = API::CURRENT_VERSION;

        // render form
        $renderer = new Renderer();
        try {
          $output = $renderer->render('form/widget.html', $data);
          $output = do_shortcode($output);
        } catch(\Exception $e) {
          $output = $e->getMessage();
        }
      }

      if($form_type === 'widget') {
        echo $output;
      } else {
        return $output;
      }
    }
  }
}
