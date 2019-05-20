<?php
namespace MailPoet\Form\Util;
use MailPoet\Form\Widget;
use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class Export {
  static function getAll($form = null) {
    return [
      'html'      => static::get('html', $form),
      'php'       => static::get('php', $form),
      'iframe'    => static::get('iframe', $form),
      'shortcode' => static::get('shortcode', $form),
    ];
  }

  static function get($type = 'html', $form = null) {
    switch ($type) {
      case 'iframe':
        // generate url to load iframe's content
        $iframe_url = WPFunctions::get()->addQueryArg([
          'mailpoet_form_iframe' => $form['id'],
        ], WPFunctions::get()->siteUrl());

        // generate iframe
        return join(' ', [
          '<iframe',
          'width="100%"',
          'height="100%"',
          'scrolling="no"',
          'frameborder="0"',
          'src="' . $iframe_url . '"',
          'class="mailpoet_form_iframe"',
          'id="mailpoet_form_iframe"',
          'vspace="0"',
          'tabindex="0"',
          'onload="if (window[\'MailPoet\']) MailPoet.Iframe.autoSize(this);"',
          'marginwidth="0"',
          'marginheight="0"',
          'hspace="0"',
          'allowtransparency="true"></iframe>',
        ]);

      case 'php':
        $output = [
          '$form_widget = new \MailPoet\Form\Widget();',
          'echo $form_widget->widget(array(\'form\' => ' .
            (int)$form['id'] .
            ', \'form_type\' => \'php\'));',
          ];
        return join("\n", $output);

      case 'html':
        $output = [];

        $output[] = '<!-- ' .
          WPFunctions::get()->__(
            'BEGIN Scripts: you should place them in the header of your theme',
            'mailpoet'
          ) .
        ' -->';

        // CSS
        $output[] = '<link rel="stylesheet" type="text/css" href="' .
          Env::$assets_url . '/dist/css/public.css?mp_ver=' . MAILPOET_VERSION .
        '" />';

        // jQuery
        $output[] = '<script type="text/javascript" src="' .
          WPFunctions::get()->includesUrl() . 'js/jquery/jquery.js?mp_ver' . MAILPOET_VERSION .
        '"></script>';

        // JS
        $output[] = '<script type="text/javascript" src="' .
          Env::$assets_url . '/dist/js/vendor.js?mp_ver=' . MAILPOET_VERSION .
        '"></script>';
        $output[] = '<script type="text/javascript" src="' .
          Env::$assets_url . '/dist/js/public.js?mp_ver=' . MAILPOET_VERSION .
        '"></script>';

        // (JS) variables...
        $output[] = '<script type="text/javascript">';
        $output[] = '   var MailPoetForm = MailPoetForm || {';
        $output[] = '       is_rtl: ' . ((int)is_rtl()) . ",";
        $output[] = '       ajax_url: "' . admin_url('admin-ajax.php') . '"';
        $output[] = '   };';
        $output[] = '</script>';
        $output[] = '<!-- ' .
          WPFunctions::get()->__('END Scripts', 'mailpoet') .
        '-->';

        $form_widget = new Widget();
        $output[] = $form_widget->widget([
          'form' => (int)$form['id'],
          'form_type' => 'php',
        ]);
        return join("\n", $output);

      case 'shortcode':
        return '[mailpoet_form id="' . (int)$form['id'] . '"]';
    }
  }
}
