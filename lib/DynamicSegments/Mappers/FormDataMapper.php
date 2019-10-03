<?php

namespace MailPoet\Premium\DynamicSegments\Mappers;

use MailPoet\Premium\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\Premium\DynamicSegments\Filters\EmailAction;
use MailPoet\Premium\DynamicSegments\Filters\Filter;
use MailPoet\Premium\DynamicSegments\Filters\UserRole;
use MailPoet\Premium\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Premium\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Premium\Models\DynamicSegment;

class FormDataMapper {

  /**
   * @param array $data
   *
   * @return DynamicSegment
   * @throws InvalidSegmentTypeException
   */
  function mapDataToDB(array $data) {
    $filters = $this->getFilters($data);
    $dynamic_segment = $this->createDynamicSegment($data);

    $dynamic_segment->setFilters($filters);

    return $dynamic_segment;
  }

  private function createDynamicSegment($data) {
    $dataToSave = [
      'name' => isset($data['name']) ? $data['name'] : '',
      'description' => isset($data['description']) ? $data['description'] : '',
    ];
    $dynamic_segment = null;
    if (isset($data['id'])) {
      $dynamic_segment = DynamicSegment::findOne($data['id']);
    }
    if ($dynamic_segment instanceof DynamicSegment) {
      $dynamic_segment->set($dataToSave);
    } else {
      $dynamic_segment = DynamicSegment::create();
      if ($dynamic_segment instanceof DynamicSegment) {
        $dynamic_segment->hydrate($dataToSave);
      }
    }
    return $dynamic_segment;
  }

  /**
   * @param array $data
   *
   * @return Filter[]
   * @throws InvalidSegmentTypeException
   */
  private function getFilters(array $data) {
    switch ($this->getSegmentType($data)) {
      case 'userRole':
        if (!$data['wordpressRole']) throw new InvalidSegmentTypeException('Missing role', InvalidSegmentTypeException::MISSING_ROLE);
        return [new UserRole($data['wordpressRole'])];
      case 'email':
        return $this->createEmail($data);
      case 'woocommerce':
        return $this->createWooCommerce($data);
      default:
        throw new InvalidSegmentTypeException('Invalid type', InvalidSegmentTypeException::INVALID_TYPE);
    }
  }

  /**
   * @param array $data
   *
   * @return string
   * @throws InvalidSegmentTypeException
   */
  private function getSegmentType(array $data) {
    if (!isset($data['segmentType'])) {
      throw new InvalidSegmentTypeException('Segment type is not set', InvalidSegmentTypeException::MISSING_TYPE);
    }
    return $data['segmentType'];
  }

  /**
   * @param array $data
   *
   * @return EmailAction[]
   * @throws InvalidSegmentTypeException
   */
  private function createEmail(array $data) {
    if (empty($data['action'])) throw new InvalidSegmentTypeException('Missing action', InvalidSegmentTypeException::MISSING_ACTION);
    if (empty($data['newsletter_id'])) throw new InvalidSegmentTypeException('Missing newsletter id', InvalidSegmentTypeException::MISSING_NEWSLETTER_ID);
    if (isset($data['link_id'])) {
      return [new EmailAction($data['action'], $data['newsletter_id'], $data['link_id'])];
    } else {
      return [new EmailAction($data['action'], $data['newsletter_id'])];
    }
  }

  /**
   * @param array $data
   *
   * @return Filter[]
   * @throws InvalidSegmentTypeException
   */
  private function createWooCommerce($data) {
    if (empty($data['action'])) throw new InvalidSegmentTypeException('Missing action', InvalidSegmentTypeException::MISSING_ACTION);
    switch ($data['action']) {
      case WooCommerceCategory::ACTION_CATEGORY:
        if (!isset($data['category_id'])) throw new InvalidSegmentTypeException('Missing category', InvalidSegmentTypeException::MISSING_CATEGORY_ID);
        return [new WooCommerceCategory($data['category_id'])];
      case WooCommerceProduct::ACTION_PRODUCT:
        if (!isset($data['product_id'])) throw new InvalidSegmentTypeException('Missing product', InvalidSegmentTypeException::MISSING_PRODUCT_ID);
        return [new WooCommerceProduct($data['product_id'])];
      default:
        throw new \InvalidArgumentException("Unknown action " . $data['action']);
    }

  }

}
