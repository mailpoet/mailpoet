<?php

if(!defined('ABSPATH')) exit;

use Symfony\Polyfill\Mbstring\Mbstring as MbstringPolyfill;

if(!function_exists('mb_detect_encoding')) {
  function mb_detect_encoding($str, $encodingList = null, $strict = false) {
    return MbstringPolyfill::mb_detect_encoding($str, $encodingList, $strict);
  }
}

if(!function_exists('mb_convert_encoding')) {
  function mb_convert_encoding($s, $to, $from = null) {
    return MbstringPolyfill::mb_convert_encoding($s, $to, $from);
  }
}
