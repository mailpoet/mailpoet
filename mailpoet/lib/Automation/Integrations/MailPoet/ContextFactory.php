<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Config\ServicesChecker;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;

class ContextFactory {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var Bridge */
  private $bridge;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var AuthorizedSenderDomainController */
  private $authorizedSenderDomainController;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  private Dependency_Check $dependencyCheck;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    Bridge $bridge,
    ServicesChecker $servicesChecker,
    AuthorizedSenderDomainController $authorizedSenderDomainController,
    AuthorizedEmailsController $authorizedEmailsController
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->servicesChecker = $servicesChecker;
    $this->bridge = $bridge;
    $this->authorizedSenderDomainController = $authorizedSenderDomainController;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->dependencyCheck = Email_Editor_Container::container()->get(Dependency_Check::class);
  }

  /** @return mixed[] */
  public function getContextData(): array {
    $data = [
      'segments' => $this->getSegments(),
      'userRoles' => $this->getUserRoles(),
      'transactional_triggers' => SendEmailAction::TRANSACTIONAL_TRIGGERS,
      'delay_action_key' => DelayAction::KEY,
      'block_email_editor_enabled' => $this->dependencyCheck->are_dependencies_met(), // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    ];

    if ($this->isMSSEnabled()) {
      $data['senderDomainsConfig'] = $this->getSenderDomainsConfig();
    }

    return $data;
  }

  private function getSenderDomainsConfig(): array {
    $senderDomainsConfig = $this->authorizedSenderDomainController->getContextDataForAutomations();
    $senderDomainsConfig['authorizedEmails'] = $this->authorizedEmailsController->getAuthorizedEmailAddresses();
    return $senderDomainsConfig;
  }

  private function isMSSEnabled(): bool {
    $mpApiKeyValid = $this->servicesChecker->isMailPoetAPIKeyValid(false, true);
    return $mpApiKeyValid && $this->bridge->isMailpoetSendingServiceEnabled();
  }

  private function getSegments(): array {
    $segments = [];
    foreach ($this->segmentsRepository->findAll() as $segment) {
      $segments[] = [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'type' => $segment->getType(),
      ];
    }
    return $segments;
  }

  private function getUserRoles(): array {
    $userRoles = [];
    foreach (wp_roles()->roles as $role => $details) {
      $userRoles[] = [
        'id' => $role,
        'name' => $details['name'],
      ];
    }
    return $userRoles;
  }
}
