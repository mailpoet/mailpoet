<?php

namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Handlebars extends \Twig_Extension {
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'partial',
        array(
          $this,
          'generatePartial'
        ),
        array(
          'needs_environment' => true,
          'needs_context' => true,
          'is_safe' => array('all')
        )
      )
    );
  }

  public function generatePartial($env, $context) {
    // get arguments (minus env & $context)
    $args = array_slice(func_get_args(), 2);
    $args_count = count($args);

    // default values
    $alias = null;

    switch($args_count) {
      case 2:
        list($id, $file) = $args;
        break;
      case 3:
        list($id, $file, $alias) = $args;
        break;
      default:
        return;
    }

    $rendered_template = twig_include($env, $context, $file);

    $output = <<<EOL
<script id="$id" type="text/x-handlebars-template">
  $rendered_template
</script>
EOL;

    if($alias !== null) {
      $output .= <<<EOL
<script type="text/javascript">
jQuery(function($) {
  $(function() {
    Handlebars.registerPartial(
      '$alias',
      jQuery('#$id').html()
    );
  });
});
</script>
EOL;
    }
    return $output;
  }
}