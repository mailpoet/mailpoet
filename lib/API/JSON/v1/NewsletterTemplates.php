<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\NewsletterTemplatesResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
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

  public function __construct(
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    NewsletterTemplatesResponseBuilder $newsletterTemplatesResponseBuilder
  ) {
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->newsletterTemplatesResponseBuilder = $newsletterTemplatesResponseBuilder;
  }

  public function get($data = []) {
    $template = isset($data['id'])
      ? $this->newsletterTemplatesRepository->findOneById((int)$data['id'])
      : null;

    if (!$template) {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This template does not exist.', 'mailpoet'),
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
    if (!empty($data['newsletter_id'])) {
      $template = NewsletterTemplate::whereEqual('newsletter_id', $data['newsletter_id'])->findOne();
      if ($template instanceof NewsletterTemplate) {
        $data['id'] = $template->id;
      }
    }

    $template = NewsletterTemplate::createOrUpdate($data);
    $errors = $template->getErrors();

    NewsletterTemplate::cleanRecentlySent($data);

    if (!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      $template = NewsletterTemplate::findOne($template->id);
      if(!$template instanceof NewsletterTemplate) return $this->errorResponse();
      return $this->successResponse(
        $template->asArray()
      );
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $template = NewsletterTemplate::findOne($id);
    if ($template instanceof NewsletterTemplate) {
      $template->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This template does not exist.', 'mailpoet'),
      ]);
    }
  }
}
