<?php

namespace MailPoet\Twig;

use Carbon\Carbon;
use MailPoet\Config\ServicesChecker;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Functions extends \Twig_Extension {

  /** @var SettingsController */
  private $settings;

  public function __construct() {
    $this->settings = new SettingsController();
  }

  function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'json_encode',
        'json_encode',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'json_decode',
        'json_decode',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_nonce_field',
        'wp_nonce_field',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'params',
        array($this, 'params'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'admin_url',
        'admin_url',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'get_option',
        'get_option',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'get_option',
        'get_option',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'sending_frequency',
        array($this, 'getSendingFrequency'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_date_format',
        array($this, 'getWPDateFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'mailpoet_version',
        array($this, 'getMailPoetVersion'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'mailpoet_premium_version',
        array($this, 'getMailPoetPremiumVersion'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'mailpoet_has_valid_premium_key',
        array($this, 'hasValidPremiumKey'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_time_format',
        array($this, 'getWPTimeFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'wp_datetime_format',
        array($this, 'getWPDateTimeFormat'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'do_action',
        'do_action',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'is_rtl',
        array($this, 'isRtl'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'number_format_i18n',
        'number_format_i18n',
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'mailpoet_locale',
        array($this, 'getTwoLettersLocale'),
        array('is_safe' => array('all'))
      ),
    );
  }

  function getSendingFrequency() {
    $args = func_get_args();
    $value = (int)array_shift($args);

    $label = null;
    $labels = array(
      'minute' => WPFunctions::get()->__('every minute', 'mailpoet'),
      'minutes' => WPFunctions::get()->__('every %1$d minutes', 'mailpoet'),
      'hour' => WPFunctions::get()->__('every hour', 'mailpoet'),
      'hours' => WPFunctions::get()->__('every %1$d hours', 'mailpoet')
    );

    if ($value >= 60) {
      // we're dealing with hours
      if ($value === 60) {
        $label = $labels['hour'];
      } else {
        $label = $labels['hours'];
      }
      $value /= 60;
    } else {
      // we're dealing with minutes
      if ($value === 1) {
        $label = $labels['minute'];
      } else {
        $label = $labels['minutes'];
      }
    }

    if ($label !== null) {
      return sprintf($label, $value);
    } else {
      return $value;
    }
  }

  function getWPDateFormat() {
    return (get_option('date_format')) ?
      WPFunctions::get()->getOption('date_format') :
      'F j, Y';
  }

  function getMailPoetVersion() {
    return MAILPOET_VERSION;
  }

  function getMailPoetPremiumVersion() {
    return (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : false;
  }

  function getWPTimeFormat() {
    return (get_option('time_format')) ?
      WPFunctions::get()->getOption('time_format') :
      'g:i a';
  }

  function getWPDateTimeFormat() {
    return sprintf('%s %s', $this->getWPDateFormat(), $this->getWPTimeFormat());
  }

  function params($key = null) {
    $args = WPFunctions::get()->stripslashesDeep($_GET);
    if (array_key_exists($key, $args)) {
      return $args[$key];
    }
    return null;
  }

  function hasValidPremiumKey() {
    $checker = new ServicesChecker();
    return $checker->isPremiumKeyValid(false);
  }

  function installedInLastTwoWeeks() {
    $max_number_of_weeks = 2;
    $installed_at = Carbon::createFromFormat('Y-m-d H:i:s', $this->settings->get('installed_at'));
    return $installed_at->diffInWeeks(Carbon::now()) < $max_number_of_weeks;
  }

  function isRtl() {
    return WPFunctions::get()->isRtl();
  }

  function getTwoLettersLocale() {
    return explode('_', WPFunctions::get()->getLocale())[0];
  }
}
