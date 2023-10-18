<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedViaForm;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\StatisticsForms;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberSubscribedViaFormTest extends \MailPoetTest {

  /** @var SubscriberSubscribedViaForm */
  private $filter;

  public function _before() {
    $this->filter = $this->diContainer->get(SubscriberSubscribedViaForm::class);
  }

  public function testItWorksWithOneForm(): void {
    $form = (new Form())->create();
    $subscriber = (new Subscriber())->withEmail('subscriber1@example.com')->create();
    (new StatisticsForms($form, $subscriber))->create();
    $form2 = (new Form())->create();
    $subscriber2 = (new Subscriber())->withEmail('subscriber2@example.com')->create();
    (new StatisticsForms($form2, $subscriber2))->create();
    $matching = $this->getMatchingEmails('any', [$form->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber1@example.com'], $matching);
    $matching = $this->getMatchingEmails('any', [$form2->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber2@example.com'], $matching);
  }

  public function testItReturnsEmptyResultIfNomatches(): void {
    $form = (new Form())->create();
    $subscriber = (new Subscriber())->withEmail('subscriber1@example.com')->create();
    (new StatisticsForms($form, $subscriber))->create();
    $matching = $this->getMatchingEmails('any', [$form->getId() + 1]);
    verify($matching)->empty();
  }

  public function testItWorksWithMultipleForms(): void {
    $form = (new Form())->create();
    $subscriber = (new Subscriber())->withEmail('subscriber1@example.com')->create();
    (new StatisticsForms($form, $subscriber))->create();
    $form2 = (new Form())->create();
    $subscriber2 = (new Subscriber())->withEmail('subscriber2@example.com')->create();
    (new StatisticsForms($form2, $subscriber2))->create();
    $form3 = (new Form())->create();
    $subscriber3 = (new Subscriber())->withEmail('subscriber3@example.com')->create();
    (new StatisticsForms($form3, $subscriber3))->create();
    $matching = $this->getMatchingEmails('any', [$form->getId(), $form2->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber1@example.com', 'subscriber2@example.com'], $matching);
  }

  public function testItWorksWithNoneOf(): void {
    $form = (new Form())->create();
    $subscriber = (new Subscriber())->withEmail('subscriber1@example.com')->create();
    (new StatisticsForms($form, $subscriber))->create();
    $form2 = (new Form())->create();
    $subscriber2 = (new Subscriber())->withEmail('subscriber2@example.com')->create();
    (new StatisticsForms($form2, $subscriber2))->create();
    $matching = $this->getMatchingEmails('none', [$form->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber2@example.com'], $matching);
    $matching = $this->getMatchingEmails('none', [$form2->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber1@example.com'], $matching);
  }

  public function testItWorksWithMultipleNoneOf(): void {
    $form = (new Form())->create();
    $subscriber = (new Subscriber())->withEmail('subscriber1@example.com')->create();
    (new StatisticsForms($form, $subscriber))->create();
    $form2 = (new Form())->create();
    $subscriber2 = (new Subscriber())->withEmail('subscriber2@example.com')->create();
    (new StatisticsForms($form2, $subscriber2))->create();
    $form3 = (new Form())->create();
    $subscriber3 = (new Subscriber())->withEmail('subscriber3@example.com')->create();
    (new StatisticsForms($form3, $subscriber3))->create();
    $matching = $this->getMatchingEmails('none', [$form->getId(), $form2->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber3@example.com'], $matching);
    $matching = $this->getMatchingEmails('none', [$form2->getId(), $form3->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber1@example.com'], $matching);
    $matching = $this->getMatchingEmails('none', [$form->getId(), $form3->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber2@example.com'], $matching);
    $matching = $this->getMatchingEmails('none', [$form->getId(), $form2->getId(), $form3->getId()]);
    $this->assertEqualsCanonicalizing([], $matching);
  }

  public function testNoneOfIncludesSubscribersWhoDidNotSubscribeViaForm(): void {
    $form = (new Form())->create();
    (new Subscriber())->withEmail('subscriber1@example.com')->create();
    $matching = $this->getMatchingEmails('none', [$form->getId()]);
    $this->assertEqualsCanonicalizing(['subscriber1@example.com'], $matching);
  }

  public function testItRetrievesLookupData(): void {
    $form = (new Form())->withName('test form')->create();
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSubscribedViaForm::TYPE, [
      'form_ids' => [$form->getId()],
      'operator' => 'none',
    ]);
    $lookupData = $this->filter->getLookupData($data);
    $this->assertEqualsCanonicalizing([
      'forms' => [
        $form->getId() => $form->getName(),
      ],
    ], $lookupData);
  }

  private function getMatchingEmails(string $operator, array $formIds): array {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSubscribedViaForm::TYPE, [
      'form_ids' => $formIds,
      'operator' => $operator,
    ]);
    return $this->tester->getSubscriberEmailsMatchingDynamicFilter($data, $this->filter);
  }
}
