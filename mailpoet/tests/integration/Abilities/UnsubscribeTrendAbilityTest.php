<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\Abilities\UnsubscribeTrendAbility;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsUnsubscribes;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class UnsubscribeTrendAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/get-unsubscribe-trend';

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before(): void {
    Carbon::setTestNow(new Carbon('2026-05-15 12:00:00'));
    $this->newsletter = (new Newsletter())->withSendingQueue()->create();
    AbilitiesTestHelper::register($this->diContainer->get(AbilitiesRegistrar::class));
    wp_set_current_user((int)$this->tester->createWordPressUser('unsubscribe-trend-editor@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    parent::_after();
  }

  public function testItReturnsOnlyMonthlyTotalsWithoutDetailedAnalytics(): void {
    $this->createUnsubscribe('2026-02-27 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK);
    $this->createUnsubscribe('2026-03-02 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK, StatisticsUnsubscribeEntity::REASON_SPAM);
    $this->createUnsubscribe('2026-03-03 10:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
    $this->createUnsubscribe('2026-05-10 10:00:00', StatisticsUnsubscribeEntity::METHOD_UNKNOWN);

    $result = $this->getAbility()->execute(['months' => 3]);

    $this->assertIsArray($result);
    verify($result)->equals([
      'months' => [
        ['month' => '2026-03', 'total' => 2],
        ['month' => '2026-04', 'total' => 0],
        ['month' => '2026-05', 'total' => 1],
      ],
      'detailed_statistics_available' => false,
    ]);
  }

  public function testItSplitsMonthlyCountsByMethodWithDetailedAnalytics(): void {
    $this->createUnsubscribe('2026-03-02 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK);
    $this->createUnsubscribe('2026-03-03 10:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
    $this->createUnsubscribe('2026-05-10 10:00:00', StatisticsUnsubscribeEntity::METHOD_UNKNOWN);

    $result = $this->createAbilityWithDetailedAnalytics()->execute(['months' => 3]);

    verify($result['detailed_statistics_available'])->true();
    verify(array_column($result['months'], 'by_method'))->equals([
      ['link' => 1, 'one_click' => 1, 'unknown' => 0],
      ['link' => 0, 'one_click' => 0, 'unknown' => 0],
      ['link' => 0, 'one_click' => 0, 'unknown' => 1],
    ]);
  }

  public function testItSplitsMonthlyCountsBySourceWithDetailedAnalytics(): void {
    $this->createUnsubscribe('2026-05-02 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK, null, StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER);
    $this->createUnsubscribe('2026-05-03 10:00:00', StatisticsUnsubscribeEntity::METHOD_UNKNOWN, null, StatisticsUnsubscribeEntity::SOURCE_MP_API);
    $this->createUnsubscribe('2026-05-04 10:00:00', StatisticsUnsubscribeEntity::METHOD_UNKNOWN, null, StatisticsUnsubscribeEntity::SOURCE_MP_API);

    $result = $this->createAbilityWithDetailedAnalytics()->execute(['months' => 1]);

    verify(array_column($result['months'], 'by_source'))->equals([[
      StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER => 1,
      StatisticsUnsubscribeEntity::SOURCE_MANAGE => 0,
      StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR => 0,
      StatisticsUnsubscribeEntity::SOURCE_ORDER_CHECKOUT => 0,
      StatisticsUnsubscribeEntity::SOURCE_AUTOMATION => 0,
      StatisticsUnsubscribeEntity::SOURCE_MP_API => 2,
      'unknown' => 0,
    ]]);
  }

  public function testItSplitsMonthlyCountsByReasonWithDetailedAnalytics(): void {
    $this->createUnsubscribe('2026-05-10 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK, StatisticsUnsubscribeEntity::REASON_SPAM);
    $this->createUnsubscribe('2026-05-11 10:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);

    $result = $this->createAbilityWithDetailedAnalytics()->execute(['months' => 1]);

    verify(array_column($result['months'], 'by_reason'))->equals([[
      StatisticsUnsubscribeEntity::REASON_NO_LONGER_INTERESTED => 0,
      StatisticsUnsubscribeEntity::REASON_DID_NOT_SIGN_UP => 0,
      StatisticsUnsubscribeEntity::REASON_INAPPROPRIATE_CONTENT => 0,
      StatisticsUnsubscribeEntity::REASON_SPAM => 1,
      StatisticsUnsubscribeEntity::REASON_OTHER => 0,
      StatisticsUnsubscribeEntity::REASON_UNSPECIFIED => 1,
    ]]);
  }

  public function testItRejectsMoreThanTwelveMonths(): void {
    $result = $this->getAbility()->execute(['months' => 13]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_input');
  }

  public function testItRejectsZeroMonths(): void {
    $result = $this->getAbility()->execute(['months' => 0]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_input');
  }

  public function testItReturnsSixMonthsWithoutInput(): void {
    $result = $this->getAbility()->execute();

    $this->assertIsArray($result);
    $this->assertIsArray($result['months']);
    verify(array_column($result['months'], 'month'))->equals(['2025-12', '2026-01', '2026-02', '2026-03', '2026-04', '2026-05']);
  }

  private function createUnsubscribe(string $createdAt, string $method, ?string $reason = null, ?string $source = null): void {
    $unsubscribe = (new StatisticsUnsubscribes($this->newsletter, (new Subscriber())->create()))
      ->withCreatedAt(new \DateTimeImmutable($createdAt))
      ->create();
    $unsubscribe->setMethod($method);
    $unsubscribe->setReason($reason);
    if ($source !== null) {
      $unsubscribe->setSource($source);
    }
    $this->entityManager->flush();
  }

  private function createAbilityWithDetailedAnalytics(): UnsubscribeTrendAbility {
    return new UnsubscribeTrendAbility(
      $this->diContainer->get(StatisticsUnsubscribesRepository::class),
      $this->make(CapabilitiesManager::class, [
        'getCapability' => new Capability('detailedAnalytics', Capability::TYPE_BOOLEAN, false),
      ]),
      $this->diContainer->get(WPFunctions::class)
    );
  }

  private function getAbility(): \WP_Ability {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability;
  }
}
