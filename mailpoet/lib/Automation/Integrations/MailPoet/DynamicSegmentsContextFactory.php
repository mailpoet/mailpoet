<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions;

class DynamicSegmentsContextFactory {
  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var Helper */
  private $woocommerceHelper;

  /** @var Functions */
  private $wp;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository,
    SegmentsRepository $segmentsRepository,
    SegmentDependencyValidator $segmentDependencyValidator,
    Helper $woocommerceHelper,
    Functions $wp
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
  }

  /** @return mixed[] */
  public function getDynamicSegmentContext(): array {
    $customFields = $this->customFieldsRepository->findBy([], ['name' => 'asc']);
    $wpRoles = $this->wp->getEditableRoles();
    $wcCountries = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getAllowedCountries() : [];
    $wooCurrencySymbol = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getWoocommerceCurrencySymbol() : '';
    return [
      'can_use_woocommerce_memberships' => $this->segmentDependencyValidator->canUseDynamicFilterType(
        DynamicSegmentFilterData::TYPE_WOOCOMMERCE_MEMBERSHIP
      ),
      'can_use_woocommerce_subscriptions' => $this->segmentDependencyValidator->canUseDynamicFilterType(
        DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION
      ),
      'custom_fields' => $this->customFieldsResponseBuilder->buildBatch($customFields),
      'membership_plans' => $this->wpPostListLoader->getMembershipPlans(),
      'newsletters_list' => $this->getNewslettersList(),
      'product_categories' => $this->wpPostListLoader->getWooCommerceCategories(),
      'products' => $this->wpPostListLoader->getProducts(),
      'static_segments_list' => $this->getStaticSegmentsList(),
      'subscription_products' => $this->wpPostListLoader->getSubscriptionProducts(),
      'tags',
      'woocommerce_countries'=>array_map(function ($code, $name) {
        return [
          'name' => $name,
          'code' => $code,
        ];
      }, array_keys($wcCountries), $wcCountries),
      'woocommerce_currency_symbol' => html_entity_decode($wooCurrencySymbol),
      'wordpress_editable_roles_list' => array_map(function($roleId, $role) {
        return [
          'role_id' => $roleId,
          'role_name' => $role['name'],
        ];
      }, array_keys($wpRoles), $wpRoles),
    ];
  }

  private function getNewslettersList(): array {
    $result = [];
    foreach ($this->newslettersRepository->getStandardNewsletterList() as $newsletter) {
      $result[] = [
        'id' => (string)$newsletter->getId(),
        'subject' => $newsletter->getSubject(),
        'sent_at' => ($sentAt = $newsletter->getSentAt()) ? $sentAt->format('Y-m-d H:i:s') : null,
      ];
    }
    return $result;
  }

  private function getStaticSegmentsList(): array {
    $list = [];
    $criteria = new Criteria();
    $criteria->where(Criteria::expr()->isNull('deletedAt'));
    $criteria->andWhere(Criteria::expr()->neq('type', SegmentEntity::TYPE_DYNAMIC));
    $criteria->orderBy(['name' => 'ASC']);
    $segments = $this->segmentsRepository->matching($criteria);
    foreach ($segments as $segment) {
      $list[] = [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'type' => $segment->getType(),
        'description' => $segment->getDescription(),
      ];
    }
    return $list;
  }
}
