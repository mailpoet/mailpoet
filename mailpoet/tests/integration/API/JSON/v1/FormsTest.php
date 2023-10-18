<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Forms;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\PreviewPage;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Tags\TagRepository;
use MailPoet\Test\DataFactories\Tag;
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

  /** @var TagRepository */
  private $tagRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Forms::class);
    $this->formsRepository = ContainerWrapper::getInstance()->get(FormsRepository::class);
    $this->tagRepository = ContainerWrapper::getInstance()->get(TagRepository::class);
    $this->wp = WPFunctions::get();
    $this->form1 = $this->createForm('Form 1');
    $this->form2 = $this->createForm('Form 2');
    $this->form3 = $this->createForm('Form 3');
    $this->createSegment('Segment 1');
    $this->createSegment('Segment 2');
  }

  public function testItCanGetAForm() {
    $response = $this->endpoint->get(/* missing id */);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => $this->form1->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->reloadForm((int)$this->form1->getId())->toArray()
    );
  }

  public function testItCanGetListingData() {
    $response = $this->endpoint->listing();

    verify($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    verify($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    verify($response->data[0]['name'])->equals('Form 1');
    verify($response->data[1]['name'])->equals('Form 2');
    verify($response->data[2]['name'])->equals('Form 3');
  }

  public function testItCanCreateANewForm() {
    $response = $this->endpoint->saveEditor();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->reloadForm((int)$response->data['id'])->toArray()
    );
    verify($response->data['name'])->equals('New form');
  }

  public function testItCanStoreDataForPreview() {
    $response = $this->endpoint->saveEditor();
    $formId = $response->data['id'];
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->reloadForm((int)$formId)->toArray()
    );
    $response->data['styles'] = '/* Custom Styles */';

    $response = $this->endpoint->previewEditor($response->data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $storedData = $this->wp->getTransient(PreviewPage::PREVIEW_DATA_TRANSIENT_PREFIX . $formId);
    expect($storedData['body'])->notEmpty();
    expect($storedData['styles'])->notEmpty();
    expect($storedData['settings'])->notEmpty();
  }

  public function testItCanSaveFormEditor() {
    $response = $this->endpoint->saveEditor();
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->toArray();
    $form['name'] = 'Updated form';

    $response = $this->endpoint->saveEditor($form);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['is_widget'])->false();
    verify($response->data['name'])->equals('Updated form');
    verify($response->data['settings']['segments_selected_by'])->equals('admin');
  }

  public function testItOnlyAdminCanSaveCustomHtml() {
    // Administrator
    wp_set_current_user(1);
    $response = $this->endpoint->saveEditor();
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->toArray();
    $form['body'][] = [
      'type' => FormEntity::HTML_BLOCK_TYPE,
      'params' => [
        'content' => 'Hello',
      ],
    ] ;
    $response = $this->endpoint->saveEditor($form);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    // Non Admin
    wp_set_current_user(0);
    $response = $this->endpoint->saveEditor($form);
    verify($response->status)->equals(APIResponse::STATUS_FORBIDDEN);
    expect($response->errors[0]['message'])->startsWith('Only administrator can');
  }

  public function testItCanExtractListsFromListSelectionBlock() {
    $response = $this->endpoint->saveEditor();
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->toArray();
    $form['body'][] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => 1], ['id' => 3]],
      ],
    ];

    $response = $this->endpoint->saveEditor($form);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['settings']['segments_selected_by'])->equals('user');
    verify($response->data['settings']['segments'])->equals([1, 3]);
  }

  public function testItCanExtractListsFromNestedListSelectionBlock() {
    $response = $this->endpoint->saveEditor();
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $form = $this->reloadForm((int)$response->data['id'])->toArray();
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
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['settings']['segments_selected_by'])->equals('user');
    verify($response->data['settings']['segments'])->equals([2, 4]);
  }

  public function testItCreatesTagsDuringSavingFormEditor(): void {
    $tag = (new Tag())
      ->withName('Tag 1')
      ->create();
    $tagName = 'Tag 2';
    $response = $this->endpoint->saveEditor([
      'settings' => [
        'tags' => [
          $tag->getName(),
          $tagName,
        ],
      ],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $tag1 = $this->tagRepository->findOneBy(['name' => $tag->getName()]);
    $this->assertEquals($tag1, $tag);
    $tag2 = $this->tagRepository->findOneBy(['name' => $tagName]);
    $this->assertInstanceOf(TagEntity::class, $tag2);
    $this->assertEquals($tag2->getName(), $tagName);
  }

  public function testItCanRestoreAForm() {
    $this->form1->setDeletedAt(new \DateTime());
    $this->formsRepository->flush();
    $this->entityManager->refresh($this->form1);

    $this->assertInstanceOf(FormEntity::class, $this->form1);
    expect($this->form1)->notNull();

    $response = $this->endpoint->restore(['id' => $this->form1->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->form1->toArray()
    );
    expect($response->data['deleted_at'])->null();
    verify($response->meta['count'])->equals(1);
  }

  public function testErrorWhenRestoringNonExistentForm() {
    $response = $this->endpoint->restore(['id' => 'Invalid ID']);
    verify($response->errors[0]['error'])->equals('not_found');
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta)->isEmpty();
  }

  public function testItCanTrashAForm() {
    $response = $this->endpoint->trash(['id' => $this->form2->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->form2->toArray()
    );
    expect($response->data['deleted_at'])->notNull();
    verify($response->meta['count'])->equals(1);
  }

  public function testErrorWhenTrashingNonExistentForm() {
    $response = $this->endpoint->trash(['id' => 'Invalid ID']);
    verify($response->errors[0]['error'])->equals('not_found');
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta)->isEmpty();
  }

  public function testItCanDeleteAForm() {
    $response = $this->endpoint->delete(['id' => $this->form3->getId()]);
    expect($response->data)->isEmpty();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(1);
  }

  public function testErrorWhenDeletingNonExistentForm() {
    $response = $this->endpoint->delete(['id' => 'Invalid ID']);
    verify($response->errors[0]['error'])->equals('not_found');
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta)->isEmpty();
  }

  public function testItCanDuplicateAForm() {
    $response = $this->endpoint->duplicate(['id' => $this->form1->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->formsRepository->findOneBy(['name' => 'Copy of Form 1']);
    $this->assertInstanceOf(FormEntity::class, $form);
    verify($response->data)->equals(
      $form->toArray()
    );
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanBulkDeleteForms() {
    $response = $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(0);
  }

  public function testItCanUpdateFormStatus() {
    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_ENABLED,
      'id' => $this->form1->getId(),
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->reloadForm((int)$this->form1->getId());
    verify($form->getStatus())->equals(FormEntity::STATUS_ENABLED);

    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_DISABLED,
      'id' => $this->form1->getId(),
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $form = $this->reloadForm((int)$this->form1->getId());
    verify($form->getStatus())->equals(FormEntity::STATUS_DISABLED);

    $response = $this->endpoint->setStatus([
      'status' => FormEntity::STATUS_DISABLED,
      'id' => 'invalid id',
    ]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);

    $response = $this->endpoint->setStatus([
      'id' => $this->form1->getId(),
    ]);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->setStatus([
      'status' => 'invalid status',
      'id' => $this->form1->getId(),
    ]);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
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

  private function reloadForm(int $id): FormEntity {
    $reloaded = $this->formsRepository->findOneById($id);
    $this->assertInstanceOf(FormEntity::class, $reloaded);
    return $reloaded;
  }
}
