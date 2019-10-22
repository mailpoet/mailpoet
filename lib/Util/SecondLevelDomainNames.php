<?php

namespace MailPoet\Util;

class SecondLevelDomainNames {

  function get($host) {
    if (preg_match('/[^.]*\.[^.]{2,3}(?:\.[^.]{2,3})?$/', $host, $matches)) {
      return $matches[0];
    }
    return $host;
  }
}
