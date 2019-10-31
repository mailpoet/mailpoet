<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Forms;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;

class FormsTest extends \MailPoetTest {

  /** @var Forms */
  private $endpoint;

  function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Forms::class);
    $this->form_1 = Form::createOrUpdate(['name' => 'Form 1']);
    $this->form_2 = Form::createOrUpdate(['name' => 'Form 2']);
    $this->form_3 = Form::createOrUpdate(['name' => 'Form 3']);
    Segment::createOrUpdate(['name' => 'Segment 1']);
    Segment::createOrUpdate(['name' => 'Segment 2']);
  }

  function testItCanGetAForm() {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => $this->form_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_1->id)->asArray()
    );
  }

  function testItCanGetListingData() {
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

  function testItCanCreateANewForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($response->data['id'])->asArray()
    );
    expect($response->data['name'])->equals('New form');
  }

  function testItCanSaveAForm() {
    $form_data = [
      'name' => 'My first form',
    ];

    $response = $this->endpoint->save($form_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'My first form')->findOne()->asArray()
    );
  }

  function testItCanPreviewAForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'New form')->findOne()->asArray()
    );

    $response = $this->endpoint->previewEditor($response->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['html'])->notEmpty();
    expect($response->data['css'])->notEmpty();
  }

  function testItCanExportAForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->exportsEditor($response->data);
    expect($response->data['html'])->notEmpty();
    expect($response->data['php'])->notEmpty();
    expect($response->data['iframe'])->notEmpty();
    expect($response->data['shortcode'])->notEmpty();
  }

  function testItCanSaveFormEditor() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = Form::findOne($response->data['id'])->asArray();
    $form['name'] = 'Updated form';

    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['is_widget'])->false();
    expect($response->data['name'])->equals('Updated form');
  }

  function testItCanRestoreAForm() {
    $this->form_1->trash();

    $trashed_form = Form::findOne($this->form_1->id);
    expect($trashed_form->deleted_at)->notNull();

    $response = $this->endpoint->restore(['id' => $this->form_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashAForm() {
    $response = $this->endpoint->trash(['id' => $this->form_2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteAForm() {
    $response = $this->endpoint->delete(['id' => $this->form_3->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDuplicateAForm() {
    $response = $this->endpoint->duplicate(['id' => $this->form_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'Copy of Form 1')->findOne()->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  function testItCanBulkDeleteForms() {
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

  function _after() {
    Form::deleteMany();
    Segment::deleteMany();
  }
}
