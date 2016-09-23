<?php
namespace MailPoet\Form\Util;
use MailPoet\Form\Widget;

class Export {
  static function getAll($form = null) {
    return array(
      'html'      => static::get('html', $form),
      'php'       => static::get('php', $form),
      'iframe'    => static::get('iframe', $form),
      'shortcode' => static::get('shortcode', $form),
    );
  }

  static function get($type = 'html', $form = null) {
    switch($type) {
      case 'iframe':
        // generate url to load iframe's content
        $iframe_url = add_query_arg(array(
          'mailpoet_form_iframe' => $form['id']
        ), site_url());

        // generate iframe
        return join(' ', array(
          '<iframe',
          'width="100%"',
          'height="100%"',
          'scrolling="no"',
          'frameborder="0"',
          'src="'.$iframe_url.'"',
          'class="mailpoet_form_iframe"',
          'vspace="0"',
          'tabindex="0"',
          'onload="if(window[\'MailPoet\']) MailPoet.Iframe.autoSize(this);"',
          'marginwidth="0"',
          'marginheight="0"',
          'hspace="0"',
          'allowtransparency="true"></iframe>'
        ));

      case 'php':
        $output = array(
          '$form_widget = new \MailPoet\Form\Widget();',
          'echo $form_widget->widget(array(\'form\' => '.(int)$form['id'].', \'form_type\' => \'php\'));'
          );
        return join("\n", $output);

      case 'html':
        // TODO: get locale setting in order to load translations
        $wp_locale = \get_locale();

        $output = array();

        $output[] = '<!-- BEGIN Scripts : you should place them in the header of your theme -->';

                        // jQuery
        $output[] = '<script type="text/javascript" src="'.includes_url().'js/jquery/jquery.js'.'?mpv='.MAILPOET_VERSION.'"></script>';

                        // (JS) form validation
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'lib/jquery.validationEngine.js?mpv='.MAILPOET_VERSION).'"></script>';
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'lib/jquery.validationEngine-en.js?mpv='.MAILPOET_VERSION).'"></script>';

                        // (CSS) form validation styles
        $output[] = '<link rel="stylesheet" type="text/css" href="'.plugins_url('wysija-newsletters/'.'lib/validationEngine.jquery.css?mpv='.MAILPOET_VERSION).'">';

                        // (JS) form submission
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'www/mailpoet_form_subscribe.js?mpv='.MAILPOET_VERSION).'"></script>';

                        // (JS) variables...
        $output[] = '<script type="text/javascript">';
        $output[] = '   var MailPoetData = MailPoetData || {';
        $output[] = '       is_rtl: '.((int)is_rtl()).",";
        $output[] = '       ajax_url: "'.admin_url('admin-ajax.php').'"';
        $output[] = '   };';
        $output[] = '</script>';
        $output[] = '<!--END Scripts-->';

        $form_widget = new Widget();
        $output[] = $form_widget->widget(array(
          'form' => (int)$form['id'],
          'form_type' => 'php'
        ));
        return join("\n", $output);

      case 'shortcode':
        return '[mailpoet_form id="'.(int)$form['id'].'"]';
    }
  }
}
