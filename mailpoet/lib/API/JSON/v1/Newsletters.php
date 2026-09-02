<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewsletterResendController;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\ConfirmationEmailCustomizer;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class Newsletters extends APIEndpoint {

  /** @var WPFunctions */
  private $wp;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var SendPreviewController */
  private $sendPreviewController;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  private NewsletterDeleteController $newsletterDeleteController;

  /** @var NewsletterResendController */
  private $newsletterResendController;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var ConfirmationEmailCustomizer */
  private $confirmationEmailCustomizer;

  public function __construct(
    WPFunctions $wp,
    NewslettersRepository $newslettersRepository,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    SendPreviewController $sendPreviewController,
    NewsletterSaveController $newsletterSaveController,
    NewsletterDeleteController $newsletterDeleteController,
    NewsletterResendController $newsletterResendController,
    NewsletterUrl $newsletterUrl,
    ConfirmationEmailCustomizer $confirmationEmailCustomizer
  ) {
    $this->wp = $wp;
    $this->newslettersRepository = $newslettersRepository;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->sendPreviewController = $sendPreviewController;
    $this->newsletterSaveController = $newsletterSaveController;
    $this->newsletterDeleteController = $newsletterDeleteController;
    $this->newsletterResendController = $newsletterResendController;
    $this->newsletterUrl = $newsletterUrl;
    $this->confirmationEmailCustomizer = $confirmationEmailCustomizer;
  }

  public function get($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
      NewslettersResponseBuilder::RELATION_OPTIONS,
      NewslettersResponseBuilder::RELATION_QUEUE,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function getWithStats($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
        NewslettersResponseBuilder::RELATION_SEGMENTS,
        NewslettersResponseBuilder::RELATION_OPTIONS,
        NewslettersResponseBuilder::RELATION_QUEUE,
        NewslettersResponseBuilder::RELATION_TOTAL_SENT,
        NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    if (!is_array($response)) {
      $response = [];
    }
    $response['preview_url'] = $this->getViewInBrowserUrl($newsletter);
    return $this->successResponse($response);
  }

  public function save($data = []) {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);
    if (!is_array($data)) {
      $data = [];
    }
    $newsletter = $this->newsletterSaveController->save($data);
    $response = $this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
    ]);
    $previewUrl = $this->getViewInBrowserUrl($newsletter);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_save_after', $response);
    return $this->successResponse($response, ['preview_url' => $previewUrl]);
  }

  public function updateShareVisibility($data = []) {
    if (!is_array($data) || !isset($data['share_visibility']) || !is_string($data['share_visibility'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('You need to specify a sharing visibility.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->newsletterSaveController->updateShareVisibility($newsletter, $data['share_visibility']);
    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response);
  }

  public function restore($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkRestore([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkTrash([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->wp->doAction('mailpoet_api_newsletters_delete_before', [$newsletter->getId()]);
      $this->newsletterDeleteController->bulkDelete([(int)$newsletter->getId()]);
      $this->wp->doAction('mailpoet_api_newsletters_delete_after', [$newsletter->getId()]);
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function showPreview($data = []) {
    if (empty($data['body'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Newsletter data is missing.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $body = $this->newsletterSaveController->decodeAndSanitizeBody($data['body']);
    if ($body === null) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Invalid newsletter body payload.', 'mailpoet'),
      ]);
    }

    $newsletter->setBody($body);
    $this->newslettersRepository->flush();

    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function sendPreview($data = []) {
    if (empty($data['subscriber'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please specify receiver information.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    try {
      $this->sendPreviewController->sendPreview($newsletter, $data['subscriber']);
    } catch (SendPreviewException $e) {
      return $this->errorResponse([APIError::BAD_REQUEST => $e->getMessage()]);
    } catch (\Throwable $e) {
      return $this->errorResponse([$e->getCode() => $e->getMessage()]);
    }
    return $this->successResponse($this->newslettersResponseBuilder->build($newsletter));
  }

  public function create($data = []) {
    try {
      $newsletter = $this->newsletterSaveController->save($data);
    } catch (ValidationException $exception) {
      return $this->badRequest(['Please specify a type.']);
    }
    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response);
  }

  public function resendToNonOpeners($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
    $subject = isset($data['subject']) ? (string)$data['subject'] : '';
    try {
      $duplicate = $this->newsletterResendController->resendToNonOpeners($newsletter, $subject);
    } catch (UnexpectedValueException $e) {
      return $this->badRequest([
        APIError::BAD_REQUEST => $e->getMessage(),
      ]);
    }
    return $this->successResponse(
      $this->newslettersResponseBuilder->build($duplicate),
      ['count' => 1]
    );
  }

  private function getNewsletter(array $data) {
    return isset($data['id'])
      ? $this->newslettersRepository->findOneById((int)$data['id'])
      : null;
  }

  private function getViewInBrowserUrl(NewsletterEntity $newsletter): string {
    $url = $this->newsletterUrl->getViewInBrowserUrl($newsletter);
    // strip protocol to avoid mix content error
    return preg_replace('/^https?:/i', '', $url);
  }

  /**
   * Get all confirmation email newsletters for use in form editor.
   * @return Response
   */
  public function getConfirmationEmails() {
    $newsletters = $this->newslettersRepository->findBy([
      'type' => NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER,
      'deletedAt' => null,
    ]);

    $result = [];
    foreach ($newsletters as $newsletter) {
      $id = $newsletter->getId();
      if ($id === null) {
        continue;
      }
      $result[] = [
        'id' => $id,
        'subject' => $newsletter->getSubject() ?: __('(no subject)', 'mailpoet'),
      ];
    }

    return $this->successResponse($result);
  }

  /**
   * Create a new confirmation email from the global default template.
   * @return Response
   */
  public function createConfirmationEmail() {
    // Get the global default confirmation email as a base
    $defaultNewsletter = $this->confirmationEmailCustomizer->getNewsletter();

    $newsletterData = [
      'type' => NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER,
      'subject' => $defaultNewsletter->getSubject(),
      'body' => json_encode($defaultNewsletter->getBody()),
    ];

    $newsletter = $this->newsletterSaveController->save($newsletterData);

    return $this->successResponse([
      'id' => $newsletter->getId(),
      'subject' => $newsletter->getSubject(),
    ]);
  }
}
