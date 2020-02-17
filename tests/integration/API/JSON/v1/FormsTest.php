<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Forms;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;

class FormsTest extends \MailPoetTest {
  public $form3;
  public $form2;
  public $form1;

  /** @var Forms */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Forms::class);
    $this->form1 = Form::createOrUpdate(['name' => 'Form 1']);
    $this->form2 = Form::createOrUpdate(['name' => 'Form 2']);
    $this->form3 = Form::createOrUpdate(['name' => 'Form 3']);
    Segment::createOrUpdate(['name' => 'Segment 1']);
    Segment::createOrUpdate(['name' => 'Segment 2']);
  }

  public function testItCanGetAForm() {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $this->endpoint->get(['id' => $this->form1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form1->id)->asArray()
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
      Form::findOne($response->data['id'])->asArray()
    );
    expect($response->data['name'])->equals('');
  }

  public function testItCanSaveAForm() {
    $formData = [
      'name' => 'My First Form',
    ];

    $response = $this->endpoint->save(Form::createOrUpdate($formData));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'My First Form')->findOne()->asArray()
    );
  }

  public function testItCanPreviewAForm() {
    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('id', $response->data['id'])->findOne()->asArray()
    );

    $response = $this->endpoint->previewEditor($response->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['html'])->notEmpty();
    expect($response->data['css'])->notEmpty();
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

    $form = Form::findOne($response->data['id'])->asArray();
    $form['name'] = 'Updated form';

    $response = $this->endpoint->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['is_widget'])->false();
    expect($response->data['name'])->equals('Updated form');
  }

  public function testItCanRestoreAForm() {
    $this->form1->trash();

    $trashedForm = Form::findOne($this->form1->id);
    expect($trashedForm->deletedAt)->notNull();

    $response = $this->endpoint->restore(['id' => $this->form1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashAForm() {
    $response = $this->endpoint->trash(['id' => $this->form2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteAForm() {
    $response = $this->endpoint->delete(['id' => $this->form3->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateAForm() {
    $response = $this->endpoint->duplicate(['id' => $this->form1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'Copy of Form 1')->findOne()->asArray()
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

  public function _after() {
    Form::deleteMany();
    Segment::deleteMany();
  }
}
