<?php

namespace MailPoet\Subscription;

class Captcha {
  const TYPE_BUILTIN = 'built-in';
  const TYPE_RECAPTCHA = 'recaptcha';
  const TYPE_DISABLED = null;

  function isSupported() {
    return extension_loaded('gd') && function_exists('imagettftext');
  }
}
