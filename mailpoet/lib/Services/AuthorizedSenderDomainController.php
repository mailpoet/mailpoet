<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Services\Bridge\API;
use MailPoet\Util\DmarcPolicyChecker;

class AuthorizedSenderDomainController {
  const DOMAIN_VERIFICATION_STATUS_VALID = 'valid';
  const DOMAIN_VERIFICATION_STATUS_INVALID = 'invalid';
  const DOMAIN_VERIFICATION_STATUS_PENDING = 'pending';

  const AUTHORIZED_SENDER_DOMAIN_ERROR_ALREADY_CREATED = 'Sender domain exist';
  const AUTHORIZED_SENDER_DOMAIN_ERROR_NOT_CREATED = 'Sender domain does not exist';
  const AUTHORIZED_SENDER_DOMAIN_ERROR_ALREADY_VERIFIED = 'Sender domain already verified';

  /** @var Bridge */
  private $bridge;

  /** @var DmarcPolicyChecker */
  private $dmarcPolicyChecker;

  /** @var null|array Cached response for with authorized domains */
  private $currentRecords = null;

  public function __construct(
    Bridge $bridge,
    DmarcPolicyChecker $dmarcPolicyChecker
  ) {
    $this->bridge = $bridge;
    $this->dmarcPolicyChecker = $dmarcPolicyChecker;
  }

  /**
   * Get record of Bridge::getAuthorizedSenderDomains
   */
  public function getDomainRecords(string $domain = ''): array {
    $records = $this->getAllRecords();
    if ($domain) {
      return $records[$domain] ?? [];
    }
    return $records;
  }

  /**
   * Get all Authorized Sender Domains
   *
   * Note: This includes both verified and unverified domains
   */
  public function getAllSenderDomains(): array {
    return $this->returnAllDomains($this->getAllRecords());
  }

  public function getAllSenderDomainsIgnoringCache(): array {
    $this->currentRecords = null;
    return $this->getAllSenderDomains();
  }

  /**
   * Get all Verified Sender Domains
   */
  public function getVerifiedSenderDomains(): array {
    return $this->returnVerifiedDomains($this->getAllRecords());
  }

  public function getVerifiedSenderDomainsIgnoringCache(): array {
    $this->currentRecords = null;
    return $this->getVerifiedSenderDomains();
  }

  /**
   * Create new Sender Domain
   *
   * Throws an InvalidArgumentException if domain already exist
   *
   * returns an Array of DNS response or array of error
   */
  public function createAuthorizedSenderDomain(string $domain): array {
    $allDomains = $this->getAllSenderDomains();

    $alreadyExist = in_array($domain, $allDomains);

    if ($alreadyExist) {
      // sender domain already created. skip making new request
      throw new \InvalidArgumentException(self::AUTHORIZED_SENDER_DOMAIN_ERROR_ALREADY_CREATED);
    }

    $finalData = $this->bridge->createAuthorizedSenderDomain($domain);

    if ($finalData && isset($finalData['error'])) {
      throw new \InvalidArgumentException($finalData['error']);
    }

    // Reset cached value since a new domain was added
    $this->currentRecords = null;

    return $finalData;
  }

  /**
   * Verify Sender Domain
   *
   * Throws an InvalidArgumentException if domain does not exist or domain is already verified
   *
   * * returns [ok: bool, dns: array] if domain verification is successful
   * * or [ok: bool, error:  string, dns: array] if domain verification failed
   * * or [error: string, status: bool] for other errors
   */
  public function verifyAuthorizedSenderDomain(string $domain): array {
    $records = $this->bridge->getAuthorizedSenderDomains();

    $allDomains = $this->returnAllDomains($records);
    $verifiedDomains = $this->returnVerifiedDomains($records);

    $alreadyExist = in_array($domain, $allDomains);

    if (!$alreadyExist) {
      // can't verify a domain that does not exist
      throw new \InvalidArgumentException(self::AUTHORIZED_SENDER_DOMAIN_ERROR_NOT_CREATED);
    }

    $alreadyVerified = in_array($domain, $verifiedDomains);

    if ($alreadyVerified) {
      // no need to reverify an already verified domain
      throw new \InvalidArgumentException(self::AUTHORIZED_SENDER_DOMAIN_ERROR_ALREADY_VERIFIED);
    }

    $response = $this->bridge->verifyAuthorizedSenderDomain($domain);

    // API response contains status, but we need to check that dns array is not included
    if ($response['status'] === API::AUTHORIZED_DOMAIN_STATUS_ERROR && !isset($response['dns'])) {
      throw new \InvalidArgumentException($response['message']);
    }

    return $response;
  }

  /**
   * Check Domain DMARC Policy
   *
   * returns `true` if domain has Restricted policy e.g. policy === reject or quarantine
   * otherwise returns `false`
   */
  public function isDomainDmarcRestricted(string $domain): bool {
    $result = $this->getDmarcPolicyForDomain($domain);
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

  /**
   * Little helper function to return All Domains. alias to `array_keys`
   *
   * The domain is the key returned from the Bridge::getAuthorizedSenderDomains
   */
  private function returnAllDomains(array $records): array {
    $domains = array_keys($records);
    return $domains;
  }

  /**
   * Little helper function to return All verified domains
   */
  private function returnVerifiedDomains(array $records): array {
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

  private function getAllRecords(): array {
    if ($this->currentRecords === null) {
      $this->currentRecords = $this->bridge->getAuthorizedSenderDomains();
    }
    return $this->currentRecords;
  }
}
