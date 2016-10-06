<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Handlebars extends \Twig_Extension {

  public function getName() {
    return 'handlebars';
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'partial',
        array($this, 'generatePartial'),
        array(
          'needs_environment' => true,
          'needs_context'     => true,
          'is_safe'           => array('all'))
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

    $output = array();

    $output[] = '<script id="'.$id.'" type="text/x-handlebars-template">';
    $output[] = twig_include($env, $context, $file);
    $output[] = '</script>';

    if($alias !== null) {
      $output[] = '<script type="text/javascript">';
      $output[] = 'jQuery(function($) {';
        $output[] = '$(function() {';
          $output[] = ' Handlebars.registerPartial(
            "'.$alias.'",
            jQuery("#'.$id.'").html());';
          $output[] = '});';
        $output[] = '});';
      $output[] = '</script>';
    }
    return join("\n", $output);
  }
}