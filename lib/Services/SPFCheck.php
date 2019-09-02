<?php

namespace MailPoet\Services;

class SPFCheck {
  function checkSPFRecord($domain) {
    $record = $this->getSPFRecord($domain);
    if (empty($record)) {
      return true;
    }
    return strpos($record, 'include:spf.sendingservice.net') !== false;
  }

  private function getSPFRecord($domain) {
    $records = $this->dnsGetRecord($domain, DNS_TXT);
    if (empty($records[0])) {
      return false;
    }
    foreach ($records as $record) {
      if (empty($record['txt']) || !preg_match('/^v=spf1/', trim($record['txt']))) {
        continue;
      }
      return $record['txt'];
    }
    return false;
  }

  protected function dnsGetRecord($domain, $type) {
    return dns_get_record($domain, $type);
  }
}
