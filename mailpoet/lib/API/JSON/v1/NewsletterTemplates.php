<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\NewsletterTemplatesResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\Newsletter\ApiDataSanitizer;
use MailPoet\Newsletter\NewsletterCoupon;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\NewsletterTemplates\ThumbnailSaver;
use MailPoet\WP\Functions as WPFunctions;

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

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /*** @var NewsletterCoupon */
  private $newsletterCoupon;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    NewsletterTemplatesResponseBuilder $newsletterTemplatesResponseBuilder,
    ThumbnailSaver $thumbnailImageSaver,
    ApiDataSanitizer $apiDataSanitizer,
    NewslettersRepository $newsletterRepository,
    NewsletterCoupon $newsletterCoupon,
    NewsletterSaveController $newsletterSaveController,
    WPFunctions $wp
  ) {
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->newsletterTemplatesResponseBuilder = $newsletterTemplatesResponseBuilder;
    $this->thumbnailImageSaver = $thumbnailImageSaver;
    $this->apiDataSanitizer = $apiDataSanitizer;
    $this->newsletterRepository = $newsletterRepository;
    $this->newsletterCoupon = $newsletterCoupon;
    $this->newsletterSaveController = $newsletterSaveController;
    $this->wp = $wp;
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
    // Do not save templates for emails created via Gutenberg editor
    $newsletterId = isset($data['newsletter_id']) ? (int)$data['newsletter_id'] : null;
    if ($newsletterId) {
      $newsletter = $this->newsletterRepository->findOneById($newsletterId);
      if ($newsletter && $newsletter->getWpPostId() !== null) {
        return $this->successResponse($data);
      }
    }
    if (isset($data['body']) && is_string($data['body']) && $data['body'] !== '') {
      $decodedBody = json_decode($data['body'], true);
      if (!is_array($decodedBody)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => __('Invalid template body payload.', 'mailpoet'),
        ]);
      }
      $body = $this->apiDataSanitizer->sanitizeBody($decodedBody);
      $body = $this->newsletterCoupon->cleanupBodySensitiveData($body);
      $data['body'] = json_encode($body);
    }
    try {
      $template = $this->newsletterTemplatesRepository->createOrUpdate($data);
      $template = $this->thumbnailImageSaver->ensureTemplateThumbnailFile($template, isset($data['thumbnail_data']));
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

    $newsletter = $template->getNewsletter();
    $this->newsletterTemplatesRepository->remove($template);
    $this->newsletterTemplatesRepository->flush();
    if ($newsletter && $newsletter->getType() === NewsletterEntity::TYPE_TEMPLATE) {
      $this->newsletterRepository->remove($newsletter);
      $this->newsletterRepository->flush();
    }
    return $this->successResponse(null, ['count' => 1]);
  }

  public function duplicate($data = []) {
    $template = $this->getTemplate($data);
    if (!$template) {
      return $this->templateNotFound();
    }

    $duplicate = $this->newsletterTemplatesRepository->duplicate($template);
    return $this->successResponse($this->newsletterTemplatesResponseBuilder->build($duplicate));
  }

  public function create($data = []) {
    $name = isset($data['name']) && is_string($data['name']) ? trim($this->wp->sanitizeTextField($data['name'])) : '';
    if ($name === '') {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please enter a template name.', 'mailpoet'),
      ]);
    }

    $template = $this->newsletterTemplatesRepository->createFromBlank($name);
    return $this->successResponse($this->newsletterTemplatesResponseBuilder->build($template));
  }

  public function rename($data = []) {
    $template = $this->getTemplate($data);
    if (!$template) {
      return $this->templateNotFound();
    }
    if ($template->getReadonly()) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('This template cannot be renamed.', 'mailpoet'),
      ]);
    }
    $name = isset($data['name']) && is_string($data['name']) ? trim($this->wp->sanitizeTextField($data['name'])) : '';
    if ($name === '') {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please enter a template name.', 'mailpoet'),
      ]);
    }

    $template->setName($name);
    $newsletter = $template->getNewsletter();
    if ($newsletter && $newsletter->getType() === NewsletterEntity::TYPE_TEMPLATE) {
      $newsletter->setSubject($name);
    }
    $this->newsletterTemplatesRepository->flush();
    return $this->successResponse($this->newsletterTemplatesResponseBuilder->build($template));
  }

  /**
   * Returns the hidden email that the editor uses to edit a saved template.
   */
  public function edit($data = []) {
    $template = $this->getTemplate($data);
    if (!$template) {
      return $this->templateNotFound();
    }
    if ($template->getReadonly()) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('MailPoet templates cannot be edited. Duplicate the template first.', 'mailpoet'),
      ]);
    }

    $newsletter = $template->getNewsletter();
    if (!$newsletter || $newsletter->getType() !== NewsletterEntity::TYPE_TEMPLATE) {
      $newsletter = $this->newsletterSaveController->save([
        'type' => NewsletterEntity::TYPE_TEMPLATE,
        'subject' => $template->getName(),
        'body' => json_encode($template->getBody()),
      ]);
      $template->setNewsletter($newsletter);
      $this->newsletterTemplatesRepository->flush();
    } else {
      // The template can change outside the editor (import, rename), so the editor always starts from it.
      $newsletter->setSubject($template->getName());
      $newsletter->setBody($template->getBody());
      $this->newsletterRepository->flush();
    }

    return $this->successResponse([
      'newsletter_id' => $newsletter->getId(),
      'editor_url' => $this->wp->adminUrl('admin.php?page=mailpoet-newsletter-editor&id=' . $newsletter->getId()),
    ]);
  }

  private function getTemplate(array $data): ?NewsletterTemplateEntity {
    return isset($data['id'])
      ? $this->newsletterTemplatesRepository->findOneById((int)$data['id'])
      : null;
  }

  private function templateNotFound() {
    return $this->errorResponse([
      APIError::NOT_FOUND => __('This template does not exist.', 'mailpoet'),
    ]);
  }
}
