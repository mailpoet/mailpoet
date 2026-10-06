<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Tag;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberTagTest extends \MailPoetTest {
  private const APPLY_HOOK = 'mailpoet_dynamic_segments_filter_subscriber_tag_apply';

  public function testItMatchesNoSubscribersWhenNoTagFilterCallbackIsRegistered(): void {
    $tag = (new Tag())->withName('VIP')->create();
    (new Subscriber())->withEmail('tagged@example.com')->withTags([$tag])->create();
    (new Subscriber())->withEmail('untagged@example.com')->create();

    $filter = new SubscriberTag($this->make(WPFunctions::class, ['hasFilter' => false]));
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($this->getTagFilterData($tag->getId()), $filter);

    verify($emails)->equals([]);
  }

  public function testItLetsTheRegisteredCallbackAddTheTagCondition(): void {
    $tag = (new Tag())->withName('VIP')->create();
    (new Subscriber())->withEmail('tagged@example.com')->withTags([$tag])->create();
    (new Subscriber())->withEmail('untagged@example.com')->create();

    $wp = $this->diContainer->get(WPFunctions::class);
    $callback = function (QueryBuilder $queryBuilder) {
      return $queryBuilder->andWhere("email = 'tagged@example.com'");
    };
    $wp->addFilter(self::APPLY_HOOK, $callback, 1, 1);
    try {
      $filter = new SubscriberTag($wp);
      $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($this->getTagFilterData($tag->getId()), $filter);
    } finally {
      $wp->removeFilter(self::APPLY_HOOK, $callback, 1);
    }

    verify($emails)->equals(['tagged@example.com']);
  }

  private function getTagFilterData(?int $tagId): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTag::TYPE, [
      'tags' => [$tagId],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]);
  }
}
