<?php declare(strict_types = 1);

namespace MailPoet\Form\Listing;

use MailPoet\Entities\FormEntity;
use MailPoet\Listing\Handler;

class FormListingRepositoryTest extends \MailPoetTest {
  /** @var Handler */
  protected $listingHandler;

  /** @var FormListingRepository */
  protected $formListingRepository;

  /** @var FormEntity */
  protected $form1;

  /** @var FormEntity */
  protected $form2;

  public function _before() {
    parent::_before();

    $this->listingHandler = new Handler();
    $this->formListingRepository = $this->diContainer->get(FormListingRepository::class);

    $this->form1 = new FormEntity('Form 1');
    $this->entityManager->persist($this->form1);
    $this->form2 = new FormEntity('Form 2');
    $this->entityManager->persist($this->form2);
    $this->entityManager->flush();
  }

  public function testItAppliesGroup() {
    // all/trash groups
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition(['group' => 'all']));
    verify($forms)->arrayCount(2);

    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition(['group' => 'trash']));
    verify($forms)->arrayCount(0);

    // delete one form
    $this->form1->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition(['group' => 'all']));
    verify($forms)->arrayCount(1);

    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition(['group' => 'trash']));
    verify($forms)->arrayCount(1);
  }

  public function testItAppliesSort() {
    // ASC
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'name',
      'sort_order' => 'asc',
    ]));
    verify($forms)->arrayCount(2);
    verify($forms[0]->getName())->same('Form 1');
    verify($forms[1]->getName())->same('Form 2');

    // DESC
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'name',
      'sort_order' => 'desc',
    ]));
    verify($forms)->arrayCount(2);
    verify($forms[0]->getName())->same('Form 2');
    verify($forms[1]->getName())->same('Form 1');
  }

  public function testItFallsBackForUnsupportedSort() {
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'status',
      'sort_order' => 'asc',
    ]));

    verify($forms)->arrayCount(2);
    verify($forms[0]->getName())->same('Form 1');
    verify($forms[1]->getName())->same('Form 2');
  }

  public function testItFiltersByStatus() {
    $this->form1->setStatus(FormEntity::STATUS_ENABLED);
    $this->form2->setStatus(FormEntity::STATUS_DISABLED);
    $this->entityManager->flush();

    $definition = $this->listingHandler->getListingDefinition(['filter' => ['status' => ['disabled']]]);
    $forms = $this->formListingRepository->getData($definition);
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 2');
    verify($this->formListingRepository->getCount($definition))->same(1);

    $definition = $this->listingHandler->getListingDefinition(['filter' => ['status' => ['enabled', 'disabled']]]);
    verify($this->formListingRepository->getData($definition))->arrayCount(2);
  }

  public function testItIgnoresUnknownStatusFilterValues() {
    $definition = $this->listingHandler->getListingDefinition(['filter' => ['status' => ['bogus']]]);
    // No valid statuses remain after the whitelist intersect, so the filter is a no-op.
    verify($this->formListingRepository->getData($definition))->arrayCount(2);
  }

  public function testItFiltersByCreatedDateRange() {
    $this->form1->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $this->form2->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->entityManager->flush();

    // from only — keeps the newer form
    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_from' => '2020-03-01']])
    );
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 2');

    // to only — keeps the older form
    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_to' => '2020-03-01']])
    );
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 1');

    // range covering both
    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['created_from' => '2020-01-01', 'created_to' => '2020-06-01']])
    );
    verify($forms)->arrayCount(2);
  }

  public function testItFiltersByModifiedDateRange() {
    // A preUpdate listener resets updatedAt on every flush, so set it with a DQL
    // UPDATE (which bypasses lifecycle events) to pin deterministic values.
    $this->setUpdatedAt($this->form1, '2020-01-01 10:00:00');
    $this->setUpdatedAt($this->form2, '2020-06-01 10:00:00');

    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['updated_from' => '2020-03-01']])
    );
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 2');

    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition(['filter' => ['updated_to' => '2020-03-01']])
    );
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 1');
  }

  public function testItFiltersByCreatedAndModifiedDateIndependently() {
    $this->form1->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $this->form2->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->entityManager->flush();
    $this->setUpdatedAt($this->form1, '2020-12-01 10:00:00');
    $this->setUpdatedAt($this->form2, '2020-06-15 10:00:00');

    // Created before March AND modified after October → only Form 1. Exercises
    // both date ranges coexisting on one query without parameter collisions.
    $forms = $this->formListingRepository->getData(
      $this->listingHandler->getListingDefinition([
        'filter' => ['created_to' => '2020-03-01', 'updated_from' => '2020-10-01'],
      ])
    );
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 1');
  }

  /** @dataProvider siteDayCases */
  public function testItFiltersCreatedDateRangeBySiteLocalDay(string $timezone, $gmtOffset, string $day, array $inside, array $outside): void {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      foreach (array_merge($inside, $outside) as $createdAt) {
        $form = new FormEntity($createdAt);
        $form->setCreatedAt(new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC')));
        $this->entityManager->persist($form);
      }
      $this->entityManager->flush();

      $forms = $this->formListingRepository->getData(
        $this->listingHandler->getListingDefinition(['filter' => ['created_from' => $day, 'created_to' => $day]])
      );
      $names = array_map(function (FormEntity $form): string {
        return $form->getName();
      }, $forms);
      sort($names);
      verify($names)->same($inside);
    });
  }

  /**
   * Site timezone, options to set, filter day, and UTC created-at values grouped by whether the
   * row belongs to that site-local day. Rows sit one second either side of the UTC day boundaries.
   *
   * @return array<string, array{string, string|float, string, string[], string[]}>
   */
  public function siteDayCases(): array {
    return [
      'utc site' => ['', 0, '2020-03-10', ['2020-03-10 00:00:00', '2020-03-10 23:59:59'], ['2020-03-09 23:59:59', '2020-03-11 00:00:00']],
      'dst zone, standard time' => ['Europe/Prague', 0, '2020-03-10', ['2020-03-09 23:00:00', '2020-03-10 22:59:59'], ['2020-03-09 22:59:59', '2020-03-10 23:00:00']],
      'dst zone, 23-hour day' => ['America/New_York', 0, '2020-03-08', ['2020-03-08 05:00:00', '2020-03-09 03:59:59'], ['2020-03-08 04:59:59', '2020-03-09 04:00:00']],
      'fixed gmt offset' => ['', 5.5, '2020-03-10', ['2020-03-09 18:30:00', '2020-03-10 18:29:59'], ['2020-03-09 18:29:59', '2020-03-10 18:30:00']],
    ];
  }

  private function withSiteTimezone(string $timezoneString, $gmtOffset, callable $callback): void {
    $previous = [
      'timezone_string' => get_option('timezone_string'),
      'gmt_offset' => get_option('gmt_offset'),
    ];
    update_option('timezone_string', $timezoneString);
    update_option('gmt_offset', $gmtOffset);
    try {
      $callback();
    } finally {
      foreach ($previous as $option => $value) {
        if ($value === false) {
          delete_option($option);
        } else {
          update_option($option, $value);
        }
      }
    }
  }

  private function setUpdatedAt(FormEntity $form, string $date): void {
    $this->entityManager
      ->createQuery('UPDATE ' . FormEntity::class . ' f SET f.updatedAt = :date WHERE f.id = :id')
      ->setParameter('date', new \DateTimeImmutable($date))
      ->setParameter('id', $form->getId())
      ->execute();
  }

  public function testItSortsByCreatedAt() {
    $this->form1->setCreatedAt(new \DateTimeImmutable('2020-06-01 10:00:00'));
    $this->form2->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
    $this->entityManager->flush();

    // ASC — oldest first
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'created_at',
      'sort_order' => 'asc',
    ]));
    verify($forms[0]->getName())->same('Form 2');
    verify($forms[1]->getName())->same('Form 1');

    // DESC — newest first
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'sort_by' => 'created_at',
      'sort_order' => 'desc',
    ]));
    verify($forms[0]->getName())->same('Form 1');
    verify($forms[1]->getName())->same('Form 2');
  }

  public function testItAppliesLimitAndOffset() {
    // first page
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 0,
    ]));
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 1');

    // second page
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 1,
    ]));
    verify($forms)->arrayCount(1);
    verify($forms[0]->getName())->same('Form 2');

    // third page
    $forms = $this->formListingRepository->getData($this->listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 2,
    ]));
    verify($forms)->arrayCount(0);
  }
}
