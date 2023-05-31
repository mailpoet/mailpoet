<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Listing\PageLimit;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\AutocompletePostListLoader as WPPostListLoader;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;

class Segments {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPPostListLoader */
  private $wpPostListLoader;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    WPFunctions $wp,
    WooCommerceHelper $woocommerceHelper,
    WPPostListLoader $wpPostListLoader,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    SegmentDependencyValidator $segmentDependencyValidator,
    SegmentsRepository $segmentsRepository,
    NewslettersRepository $newslettersRepository,
    FormsRepository $formsRepository
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->wp = $wp;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wpPostListLoader = $wpPostListLoader;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->segmentsRepository = $segmentsRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->formsRepository = $formsRepository;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');

    $customFields = $this->customFieldsRepository->findBy([], ['name' => 'asc']);
    $data['custom_fields'] = $this->customFieldsResponseBuilder->buildBatch($customFields);

    $wpRoles = $this->wp->getEditableRoles();
    $data['wordpress_editable_roles_list'] = array_map(function($roleId, $role) {
      return [
        'role_id' => $roleId,
        'role_name' => $role['name'],
      ];
    }, array_keys($wpRoles), $wpRoles);

    $data['newsletters_list'] = $this->getNewslettersList();

    $data['static_segments_list'] = [];
    $criteria = new Criteria();
    $criteria->where(Criteria::expr()->isNull('deletedAt'));
    $criteria->andWhere(Criteria::expr()->neq('type', SegmentEntity::TYPE_DYNAMIC));
    $criteria->orderBy(['name' => 'ASC']);
    $segments = $this->segmentsRepository->matching($criteria);
    foreach ($segments as $segment) {
      $data['static_segments_list'][] = [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'type' => $segment->getType(),
        'description' => $segment->getDescription(),
      ];
    }

    $data['product_categories'] = $this->wpPostListLoader->getWooCommerceCategories();

    $data['products'] = $this->wpPostListLoader->getProducts();
    $data['membership_plans'] = $this->wpPostListLoader->getMembershipPlans();
    $data['subscription_products'] = $this->wpPostListLoader->getSubscriptionProducts();
    $wcCountries = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getAllowedCountries() : [];
    $data['woocommerce_countries'] = array_map(function ($code, $name) {
      return [
        'name' => $name,
        'code' => $code,
      ];
    }, array_keys($wcCountries), $wcCountries);
    $data['can_use_woocommerce_memberships'] = $this->segmentDependencyValidator->canUseDynamicFilterType(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE_MEMBERSHIP
    );
    $data['can_use_woocommerce_subscriptions'] = $this->segmentDependencyValidator->canUseDynamicFilterType(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION
    );
    $wooCurrencySymbol = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getWoocommerceCurrencySymbol() : '';
    $data['woocommerce_currency_symbol'] = html_entity_decode($wooCurrencySymbol);
    $data['signup_forms'] = array_map(function(FormEntity $form) {
      return [
        'id' => $form->getId(),
        'name' => $form->getName(),
      ];
    }, $this->formsRepository->findAll());

    $data['woocommerce_payment_methods'] = [];
    $data['woocommerce_shipping_methods'] = [];

    if ($this->woocommerceHelper->isWooCommerceActive()) {
      $allGateways = $this->woocommerceHelper->getPaymentGateways()->payment_gateways();
      $paymentMethods = [];
      foreach ($allGateways as $gatewayId => $gateway) {
        $paymentMethods[] = [
          'id' => $gatewayId,
          'name' => $gateway->get_method_title(),
        ];
      }
      $data['woocommerce_payment_methods'] = $paymentMethods;

      $shippingMethods = [];
      foreach ($this->woocommerceHelper->getShippingMethods() as $shippingMethod) {
        $shippingMethods[] = [
          'name' => $shippingMethod->get_method_title(),
        ];
      }
      $data['woocommerce_shipping_methods'] = $shippingMethods;
    }

    $this->pageRenderer->displayPage('segments.html', $data);
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
}
