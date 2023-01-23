<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\NewsletterTemplatesResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Newsletter\ApiDataSanitizer;
use MailPoet\Newsletter\NewsletterCoupon;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\NewsletterTemplates\ThumbnailSaver;

class NewsletterTemplates extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  protected static $getMethods = [
    'getAll',
  ];

  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  /** @var NewsletterTemplatesResponseBuilder */
  private $newsletterTemplatesResponseBuilder;

  /** @var ThumbnailSaver */
  private $thumbnailImageSaver;

  /** @var ApiDataSanitizer */
  private $apiDataSanitizer;


  /*** @var NewsletterCoupon */
  private $newsletterCoupon;

  public function __construct(
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    NewsletterTemplatesResponseBuilder $newsletterTemplatesResponseBuilder,
    ThumbnailSaver $thumbnailImageSaver,
    ApiDataSanitizer $apiDataSanitizer,
    NewsletterCoupon $newsletterCoupon
  ) {
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->newsletterTemplatesResponseBuilder = $newsletterTemplatesResponseBuilder;
    $this->thumbnailImageSaver = $thumbnailImageSaver;
    $this->apiDataSanitizer = $apiDataSanitizer;
    $this->newsletterCoupon = $newsletterCoupon;
  }

  public function get($data = []) {
    $template = isset($data['id'])
      ? $this->newsletterTemplatesRepository->findOneById((int)$data['id'])
      : null;

    if (!$template) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This template does not exist.', 'mailpoet'),
      ]);
    }

    $data = $this->newsletterTemplatesResponseBuilder->build($template);
    return $this->successResponse($data);
  }

  public function getAll() {
    $templates = $this->newsletterTemplatesRepository->findAllForListing();
    $data = $this->newsletterTemplatesResponseBuilder->buildForListing($templates);
    return $this->successResponse($data);
  }

  public function save($data = []) {
    ignore_user_abort(true);
    if (!empty($data['body'])) {
      $body = $this->apiDataSanitizer->sanitizeBody(json_decode($data['body'], true));
      $body = $this->newsletterCoupon->cleanupBodySensitiveData($body);
      $data['body'] = json_encode($body);
    }
    try {
      $template = $this->newsletterTemplatesRepository->createOrUpdate($data);
      $template = $this->thumbnailImageSaver->ensureTemplateThumbnailFile($template);
      if (!empty($data['categories']) && $data['categories'] === NewsletterTemplatesRepository::RECENTLY_SENT_CATEGORIES) {
        $this->newsletterTemplatesRepository->cleanRecentlySent();
      }
      $data = $this->newsletterTemplatesResponseBuilder->build($template);
      return $this->successResponse($data);
    } catch (\Throwable $e) {
      return $this->errorResponse();
    }
  }

  public function delete($data = []) {
    $template = isset($data['id'])
      ? $this->newsletterTemplatesRepository->findOneById((int)$data['id'])
      : null;

    if (!$template) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This template does not exist.', 'mailpoet'),
      ]);
    }

    $this->newsletterTemplatesRepository->remove($template);
    $this->newsletterTemplatesRepository->flush();
    return $this->successResponse(null, ['count' => 1]);
  }
}
