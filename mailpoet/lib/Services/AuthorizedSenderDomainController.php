<?php

namespace MailPoet\Services;

use MailPoet\Util\DmarcPolicyChecker;

class AuthorizedSenderDomainController {
  const DOMAIN_VERIFICATION_STATUS_VALID = 'valid';
  const DOMAIN_VERIFICATION_STATUS_INVALID = 'invalid';
  const DOMAIN_VERIFICATION_STATUS_PENDING = 'pending';

  /** @var Bridge */
  private $bridge;

  /** @var DmarcPolicyChecker */
  private $dmarcPolicyChecker;

  public function __construct(
    Bridge $bridge,
    DmarcPolicyChecker $dmarcPolicyChecker
  ) {
    $this->bridge = $bridge;
    $this->dmarcPolicyChecker = $dmarcPolicyChecker;
  }

  /**
   * Get all Authorized Sender Domains
   *
   * Note: This includes both verified and unverified domains
   */
  public function getAllSenderDomains(): array {
    $records = $this->bridge->getAuthorizedSenderDomains();
    $domains = array_keys($records);
    return $domains;
  }

  /**
   * Get all Verified Sender Domains
   */
  public function getVerifiedSenderDomains(): array {
    $records = $this->bridge->getAuthorizedSenderDomains();
    $verifiedDomains = [];

    foreach ($records as $key => $value) {
      if (count($value) < 3) continue;
      [$domainKey1, $domainKey2, $secretRecord] = $value;
      if (
        $domainKey1['status'] === self::DOMAIN_VERIFICATION_STATUS_VALID &&
        $domainKey2['status'] === self::DOMAIN_VERIFICATION_STATUS_VALID &&
        $secretRecord['status'] === self::DOMAIN_VERIFICATION_STATUS_VALID
      ) {
        $verifiedDomains[] = $key;
      }
    }

    return $verifiedDomains;
  }

  /**
   * Check Domain DMARC Policy
   *
   * returns `true` if domain has Retricted policy e.g policy === reject or quarantine
   * otherwise returns `false`
   */
  public function isDomainDmarcRetricted(string $domain): bool {
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    return $result !== DmarcPolicyChecker::POLICY_NONE;
  }

  /**
   * Fetch Domain DMARC Policy
   *
   * returns reject or quarantine or none
   */
  public function getDmarcPolicyForDomain(string $domain): string {
    return $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
  }
}
