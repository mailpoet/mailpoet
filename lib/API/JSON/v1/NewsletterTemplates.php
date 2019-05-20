<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class NewsletterTemplates extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $template = NewsletterTemplate::findOne($id);
    if ($template instanceof NewsletterTemplate) {
      return $this->successResponse(
        $template->asArray()
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This template does not exist.', 'mailpoet'),
      ]);
    }
  }

  function getAll() {
    $collection = NewsletterTemplate
      ::selectExpr('id, categories, thumbnail, name, description, readonly')
      ->orderByAsc('readonly')
      ->orderByDesc('created_at')
      ->orderByDesc('id')
      ->findMany();
    $templates = array_map(function($item) {
      return $item->asArray();
    }, $collection);

    return $this->successResponse($templates);
  }

  function save($data = []) {
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

  function delete($data = []) {
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
