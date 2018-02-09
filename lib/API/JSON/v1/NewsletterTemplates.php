<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Models\NewsletterTemplate;

if(!defined('ABSPATH')) exit;

class NewsletterTemplates extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS
  );

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $template = NewsletterTemplate::findOne($id);
    if($template === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This template does not exist.', 'mailpoet')
      ));
    } else {
      return $this->successResponse(
        $template->asArray()
      );
    }
  }

  function getAll() {
    $collection = NewsletterTemplate::orderByDesc('created_at')->orderByAsc('name')->findMany();
    $templates = array_map(function($item) {
      return $item->asArray();
    }, $collection);

    return $this->successResponse($templates);
  }

  function save($data = array()) {
    if(!empty($data['newsletter_id'])) {
      $template = NewsletterTemplate::whereEqual('newsletter_id', $data['newsletter_id'])->findOne();
      if(!empty($template)) {
        $template = $template->asArray();
        $data['id'] = $template['id'];
      }
    }

    $template = NewsletterTemplate::createOrUpdate($data);
    $errors = $template->getErrors();

    NewsletterTemplate::cleanRecentlySent($data);

    if(!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      return $this->successResponse(
        NewsletterTemplate::findOne($template->id)->asArray()
      );
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $template = NewsletterTemplate::findOne($id);
    if($template === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This template does not exist.', 'mailpoet')
      ));
    } else {
      $template->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }
}
