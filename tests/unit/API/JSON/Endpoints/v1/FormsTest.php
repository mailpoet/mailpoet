<?php
use MailPoet\API\JSON\Endpoints\v1\Forms;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;

class FormsTest extends MailPoetTest {
  function _before() {
    $this->form_1 = Form::createOrUpdate(array('name' => 'Form 1'));
    $this->form_2 = Form::createOrUpdate(array('name' => 'Form 2'));
    $this->form_3 = Form::createOrUpdate(array('name' => 'Form 3'));
    Segment::createOrUpdate(array('name' => 'Segment 1'));
    Segment::createOrUpdate(array('name' => 'Segment 2'));
  }

  function testItCanGetAForm() {
    $router = new Forms();

    $response = $router->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This form does not exist.');

    $response = $router->get(array('id' => $this->form_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_1->id)->asArray()
    );
  }

  function testItCanGetListingData() {
    $router = new Forms();
    $response = $router->listing();

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
    $router = new Forms();
    $response = $router->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($response->data['id'])->asArray()
    );
    expect($response->data['name'])->equals('New form');
  }

  function testItCanSaveAForm() {
    $form_data = array(
      'name' => 'My first form'
    );

    $router = new Forms();
    $response = $router->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');

    $response = $router->save($form_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'My first form')->findOne()->asArray()
    );
  }

  function testItCanPreviewAForm() {
    $router = new Forms();

    $response = $router->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'New form')->findOne()->asArray()
    );

    $response = $router->previewEditor($response->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['html'])->notEmpty();
    expect($response->data['css'])->notEmpty();
  }

  function testItCanExportAForm() {
    $router = new Forms();

    $response = $router->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $router->exportsEditor($response->data);
    expect($response->data['html'])->notEmpty();
    expect($response->data['php'])->notEmpty();
    expect($response->data['iframe'])->notEmpty();
    expect($response->data['shortcode'])->notEmpty();
  }

  function testItCanSaveFormEditor() {
    $router = new Forms();

    $response = $router->create();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $form = Form::findOne($response->data['id'])->asArray();
    $form['name'] = 'Updated form';

    $response = $router->saveEditor($form);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['is_widget'])->false();
    expect($response->data['name'])->equals('Updated form');
  }

  function testItCanRestoreAForm() {
    $this->form_1->trash();

    $trashed_form = Form::findOne($this->form_1->id);
    expect($trashed_form->deleted_at)->notNull();

    $router = new Forms();
    $response = $router->restore(array('id' => $this->form_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashAForm() {
    $router = new Forms();
    $response = $router->trash(array('id' => $this->form_2->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::findOne($this->form_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteAForm() {
    $router = new Forms();
    $response = $router->delete(array('id' => $this->form_3->id));
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDuplicateAForm() {
    $router = new Forms();
    $response = $router->duplicate(array('id' => $this->form_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Form::where('name', 'Copy of Form 1')->findOne()->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  function testItCanBulkDeleteForms() {
    $router = new Forms();
    $response = $router->bulkAction(array(
      'action' => 'trash',
      'listing' => array('group' => 'all')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $router = new Forms();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function _after() {
    Form::deleteMany();
    Segment::deleteMany();
  }
}
