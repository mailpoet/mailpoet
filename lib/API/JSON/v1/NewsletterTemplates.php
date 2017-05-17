<?php
namespace MailPoet\API\JSON\v1;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;

use MailPoet\Models\NewsletterTemplate;

if(!defined('ABSPATH')) exit;

class NewsletterTemplates extends APIEndpoint {
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
    $collection = NewsletterTemplate::findMany();
    $templates = array_map(function($item) {
      return $item->asArray();
    }, $collection);

    return $this->successResponse($templates);
  }

  function save($data = array()) {
    $template = NewsletterTemplate::createOrUpdate($data);
    $errors = $template->getErrors();

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
