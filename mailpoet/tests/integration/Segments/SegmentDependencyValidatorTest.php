<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTag;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTextField;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class SegmentDependencyValidatorTest extends \MailPoetTest {
  public function testItMissingPluginsForWooCommerceDynamicSegment(): void {
    $dynamicSegment = $this->createSegment(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCategory::ACTION_CATEGORY,
      [
        'category_ids' => ['1'],
        'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      ]
    );
    // Plugin is not active
    $validator = $this->createValidator(false);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    verify($missingPlugins)->equals(['WooCommerce']);

    // Plugin is active
    $validator = $this->createValidator(true);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    verify($missingPlugins)->equals([]);
  }

  public function testItReportsPremiumMissingForTagFilterWhenPremiumIsActiveButNotRunning(): void {
    $segment = $this->createTagSegment();
    $validator = $this->createValidatorWithPremiumRunning(false);
    verify($validator->getMissingPluginsBySegment($segment))->equals(['MailPoet Premium']);
  }

  public function testItReportsNothingMissingForTagFilterWhenPremiumIsRunning(): void {
    $segment = $this->createTagSegment();
    $validator = $this->createValidatorWithPremiumRunning(true);
    verify($validator->getMissingPluginsBySegment($segment))->equals([]);
  }

  public function testItKeepsMultipleFreeFiltersUsableWhenPremiumIsActiveButNotRunning(): void {
    $segment = $this->createSegment(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'user']);
    $this->addFilter($segment, DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::FIRST_NAME, ['operator' => 'is', 'value' => 'User7']);
    $validator = $this->createValidatorWithPremiumRunning(false);
    verify($validator->getMissingPluginsBySegment($segment))->equals([]);
  }

  public function testItReportsPremiumMissingForMultipleFiltersWhenPremiumKeyIsInvalid(): void {
    $segment = $this->createSegment(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::EMAIL, ['operator' => 'startsWith', 'value' => 'user']);
    $this->addFilter($segment, DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTextField::FIRST_NAME, ['operator' => 'is', 'value' => 'User7']);
    $validator = $this->createValidator(true, false);
    verify($validator->getMissingPluginsBySegment($segment))->equals(['MailPoet Premium']);
  }

  public function testPremiumIsNotRunningWhenActiveButNotInitialized(): void {
    if (defined('MAILPOET_PREMIUM_INITIALIZED')) {
      $this->markTestSkipped('MailPoet Premium is initialized in this test run.');
    }
    verify($this->createValidator(true)->isPremiumPluginRunning())->false();
  }

  private function createTagSegment(): SegmentEntity {
    return $this->createSegment(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTag::TYPE, [
      'tags' => [1],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]);
  }

  private function addFilter(SegmentEntity $segment, string $filterType, string $action, array $filterData): void {
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData($filterType, $action, $filterData));
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
  }

  private function createValidatorWithPremiumRunning(bool $isPremiumRunning): SegmentDependencyValidator {
    return $this->construct(
      SegmentDependencyValidator::class,
      [
        $this->make(SubscribersFeature::class, ['hasValidPremiumKey' => true, 'check' => false]),
        $this->make(WPFunctions::class, ['isPluginActive' => true]),
      ],
      ['isPremiumPluginRunning' => $isPremiumRunning]
    );
  }

  private function createSegment(string $filterType, string $action, array $filterData): SegmentEntity {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterData = new DynamicSegmentFilterData($filterType, $action, $filterData);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $segment;
  }

  private function createValidator(
    bool $isPluginActive,
    bool $hasValidPremiumKey = true,
    bool $subscribersLimitReached = false
  ): SegmentDependencyValidator {
    $wp = $this->make(WPFunctions::class, [
      'isPluginActive' => $isPluginActive,
    ]);
    $subscribersFeature = $this->make(SubscribersFeature::class, [
      'hasValidPremiumKey' => $hasValidPremiumKey,
      'check' => $subscribersLimitReached,
    ]);
    return new SegmentDependencyValidator($subscribersFeature, $wp);
  }
}
