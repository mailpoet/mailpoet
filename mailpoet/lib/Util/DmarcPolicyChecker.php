<?php

namespace MailPoet\Util;

class DmarcPolicyChecker {
  const POLICY_NONE = 'none';
  const POLICY_REJECT = 'reject';
  const POLICY_QUARANTINE = 'quarantine';

  /**
   * Lookup the Domain DMARC Policy
   * returns reject or quarantine or none
   */
  public function getDomainDmarcPolicy(string $domain): string {

    if (!$domain) {
      throw new \InvalidArgumentException('Domain is Required');
    }

    $dnsLookup = dns_get_record("_dmarc.$domain", DNS_TXT);

    if (!is_array($dnsLookup)) {
      return self::POLICY_NONE;
    }

    $txtRecord = $dnsLookup[0]['txt'] ?? null;

    if (!$txtRecord) {
        // note
        // most DNS may not have this record
        // good to set policy to none in those cases
        return self::POLICY_NONE;
    }

    // Check for the presence of v=DMARC1;
    if (stripos($txtRecord, 'dmarc') === false) {
      // this is not a dmarc txt record
      // probably a wrong setup from the user
      return self::POLICY_NONE;
    }

    $cache = explode(';', $txtRecord);

    $dmarcInfo = [];

    foreach ($cache as $value) {
        $item = explode('=', $value);
        $dKey = $item[0] ?? '';
        $dValue = $item[1] ?? '';
        $dmarcInfo[strtolower(trim($dKey))] = strtolower(trim($dValue));
    }

    // policy can either be reject or quarantine or none
    $dmarcStatus = $dmarcInfo['p'] ?? self::POLICY_NONE;
    // check for subdomain policy
    $dmarcStatus = (
      isset($dmarcInfo['sp']) &&
      ($dmarcInfo['sp'] === self::POLICY_QUARANTINE ||
      $dmarcInfo['sp'] === self::POLICY_REJECT)
    ) ? $dmarcInfo['sp'] : $dmarcStatus;

    return $dmarcStatus;
  }
}
