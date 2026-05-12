<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Features\FeaturesController;
use MailPoet\WordPress\TransactionalEmails\WpTransactionalEmailManager;
use MailPoet\WordPress\TransactionalEmails\WpTransactionalEmails as WpTransactionalEmailsService;

class WpTransactionalEmails extends APIEndpoint {
  /** @var array<string, string> */
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var WpTransactionalEmailManager */
  private $manager;

  /** @var WpTransactionalEmailsService */
  private $service;

  /** @var FeaturesController */
  private $features;

  public function __construct(
    WpTransactionalEmailManager $manager,
    WpTransactionalEmailsService $service,
    FeaturesController $features
  ) {
    $this->manager = $manager;
    $this->service = $service;
    $this->features = $features;
  }

  /**
   * @param array<string, mixed> $data
   * @return Response
   */
  public function listAll($data = []): Response {
    if (!$this->isFeatureEnabled()) {
      return $this->featureDisabled();
    }
    return $this->successResponse($this->manager->listAll());
  }

  /**
   * @param array<string, mixed> $data
   * @return Response
   */
  public function customize($data = []): Response {
    if (!$this->isFeatureEnabled()) {
      return $this->featureDisabled();
    }
    $kind = is_string($data['kind'] ?? null) ? $data['kind'] : '';
    if (!$this->service->isValidKind($kind)) {
      return $this->badRequest([APIError::BAD_REQUEST => __('Unknown email kind.', 'mailpoet')]);
    }
    $newsletter = $this->manager->findOrCreate($kind);
    if ($newsletter === null) {
      return $this->errorResponse([APIError::UNKNOWN => __('Could not create the customised email.', 'mailpoet')]);
    }
    return $this->successResponse([
      'kind' => $kind,
      'newsletter_id' => $newsletter->getId(),
      'edit_url' => $this->manager->getEditUrl($newsletter),
    ]);
  }

  /**
   * @param array<string, mixed> $data
   * @return Response
   */
  public function setActive($data = []): Response {
    if (!$this->isFeatureEnabled()) {
      return $this->featureDisabled();
    }
    $kind = is_string($data['kind'] ?? null) ? $data['kind'] : '';
    $active = (bool)($data['active'] ?? false);
    if (!$this->service->isValidKind($kind)) {
      return $this->badRequest([APIError::BAD_REQUEST => __('Unknown email kind.', 'mailpoet')]);
    }
    if (!$this->manager->setActive($kind, $active)) {
      return $this->errorResponse([APIError::NOT_FOUND => __('No customised email exists for this kind.', 'mailpoet')]);
    }
    return $this->successResponse(['kind' => $kind, 'active' => $active]);
  }

  /**
   * @param array<string, mixed> $data
   * @return Response
   */
  public function restoreDefault($data = []): Response {
    if (!$this->isFeatureEnabled()) {
      return $this->featureDisabled();
    }
    $kind = is_string($data['kind'] ?? null) ? $data['kind'] : '';
    if (!$this->service->isValidKind($kind)) {
      return $this->badRequest([APIError::BAD_REQUEST => __('Unknown email kind.', 'mailpoet')]);
    }
    if (!$this->manager->restoreDefault($kind)) {
      return $this->errorResponse([APIError::UNKNOWN => __('Could not restore the default email.', 'mailpoet')]);
    }
    return $this->successResponse(['kind' => $kind]);
  }

  private function isFeatureEnabled(): bool {
    return $this->features->isSupported(FeaturesController::FEATURE_WP_TRANSACTIONAL_EMAILS);
  }

  private function featureDisabled(): Response {
    return $this->errorResponse(
      [APIError::FORBIDDEN => __('WordPress email customisation is not enabled.', 'mailpoet')],
      [],
      Response::STATUS_FORBIDDEN
    );
  }
}
