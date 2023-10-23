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
