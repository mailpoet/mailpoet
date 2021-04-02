<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Forms;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\PreviewPage;
use MailPoet\Models\Form;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;

class FormsTest extends \MailPoetTest {
  public $form3;
  public $form2;
  public $form1;

  /** @var Forms */
  private $endpoint;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Forms::class);
    $this->formsRepository = ContainerWrapper::getInstance()->get(FormsRepository::class);
    $this->wp = WPFunctions::get();
    $this->form1 = $this->createForm('Form 1');
    $this->form2 = $this->createForm('Form 2');
    $this->form3 = $this->createForm('Form 3');
    $this->createSegment('Segment 1');
    $this->createSegment('Segment 2');
  }

  public function testItCanGetAForm() {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => $this->form1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->reloadForm((int)$this->form1->getId())->asArray()
    );
  }

  public function testItCanGetListingData() {
    $response = $this->endpoint->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    expect($response->data[0]['name'])->equals('Form 1');
    expect($response->data[1]['name'])->equals('Form 2');
    expect($response->data[2]['name'])->equals('Form 3');
  }

  public function testItCanCreateANewForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->reloadForm((int)$response->data['id'])->asArray()
    );
    expect($response->data['name'])->equals('');
  }

  public function testItCanStoreDataForPreview() {
    $response = $this->endpoint->create();
    $formId = $response->data['id'];
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->reloadForm((int)$formId)->asArray()
    );
    $response->data['styles'] = '/* Custom Styles */';

    $response = $this->endpoint->previewEditor($response->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $storedData = $this->wp->getTransient(PreviewPage::PREVIEW_DATA_TRANSIENT_PREFIX . $formId);
    expect($storedData['body'])->notEmpty();
    expect($storedData['styles'])->notEmpty();
    expect($storedData['settings'])->notEmpty();
  }

  public function testItCanExportAForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->exportsEditor($response->data);
    expect($response->data['html'])->notEmpty();
    expect($response->data['php'])->notEmpty();
    expect($response->data['iframe'])->notEmpty();
    expect($response->data['shortcode'])->notEmpty();
  }

  public function testItCanSaveFormEditor() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->asArray();
    $form['name'] = 'Updated form';

    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['is_widget'])->false();
    expect($response->data['name'])->equals('Updated form');
    expect($response->data['settings']['segments_selected_by'])->equals('admin');
  }

  public function testItOnlyAdminCanSaveCustomHtml() {
    // Administrator
    wp_set_current_user(1);
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->asArray();
    $form['body'][] = [
      'type' => FormEntity::HTML_BLOCK_TYPE,
      'params' => [
        'content' => 'Hello',
      ],
    ] ;
    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    // Non Admin
    wp_set_current_user(0);
    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_FORBIDDEN);
    expect($response->errors[0]['message'])->startsWith('Only administrator can');
  }

  public function testItCanExtractListsFromListSelectionBlock() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->asArray();
    $form['body'][] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => 1], ['id' => 3]],
      ],
    ];

    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['settings']['segments_selected_by'])->equals('user');
    expect($response->data['settings']['segments'])->equals([1, 3]);
  }

  public function testItCanExtractListsFromNestedListSelectionBlock() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->asArray();
    $form['body'][] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => 2], ['id' => 4]],
      ],
    ];

    $form['body'] = [
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'body' => $form['body'],
          ],
        ],
      ],
    ];

    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['settings']['segments_selected_by'])->equals('user');
    expect($response->data['settings']['segments'])->equals([2, 4]);
  }

  public function testItCanRestoreAForm() {
    $this->form1->setDeletedAt(new \DateTime());
    $this->formsRepository->flush();
    $this->entityManager->refresh($this->form1);

    assert($this->form1 instanceof FormEntity);
    expect($this->form1)->notNull();

    $response = $this->endpoint->restore(['id' => $this->form1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->reloadForm((int)$this->form1->getId())->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashAForm() {
    $response = $this->endpoint->trash(['id' => $this->form2->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->form2->toArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testErrorWhenTrashingNonExistentForm() {
    $response = $this->endpoint->trash(['id' => 'Invalid ID']);
    expect($response->errors[0]['error'])->equals('not_found');
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta)->isEmpty();
  }

  public function testItCanDeleteAForm() {
    $response = $this->endpoint->delete(['id' => $this->form3->getId()]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateAForm() {
    $response = $this->endpoint->duplicate(['id' => $this->form1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->formsRepository->findOneBy(['name' => 'Copy of Form 1']);
    $this->assertInstanceOf(FormEntity::class, $form);
    expect($response->data)->equals(
      $form->toArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanBulkDeleteForms() {
    $response = $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  public function testItCanUpdateFormStatus() {
    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_ENABLED,
      'id' => $this->form1->getId(),
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->reloadForm((int)$this->form1->getId());
    expect($form->status)->equals(FormEntity::STATUS_ENABLED);

    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_DISABLED,
      'id' => $this->form1->getId(),
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->reloadForm((int)$this->form1->getId());
    expect($form->status)->equals(FormEntity::STATUS_DISABLED);

    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_DISABLED,
      'id' => 'invalid id',
    ]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);

    $response = $this->endpoint->setStatus([
      'id' => $this->form1->getId(),
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->setStatus([
      'status' => 'invalid status',
      'id' => $this->form1->getId(),
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  private function createForm(string $name): FormEntity {
    $form = new FormEntity($name);
    $this->formsRepository->persist($form);
    $this->formsRepository->flush();
    return $form;
  }

  private function createSegment(string $name) {
    $segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'Some description');
    $segmentsRepository->persist($segment);
    $segmentsRepository->flush();
  }

  private function reloadForm(int $id): Form {
    $reloaded = Form::findOne($id);
    assert($reloaded instanceof Form);
    return $reloaded;
  }

  public function _after() {
    $this->truncateEntity(FormEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
