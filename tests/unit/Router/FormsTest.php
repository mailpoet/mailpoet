<?php
use \MailPoet\Router\Forms;
use \MailPoet\Models\Form;
use \MailPoet\Models\Segment;

class FormsTest extends MailPoetTest {
  function _before() {
    Form::createOrUpdate(array('name' => 'Form 1'));
    Form::createOrUpdate(array('name' => 'Form 2'));
    Form::createOrUpdate(array('name' => 'Form 3'));
    Segment::createOrUpdate(array('name' => 'Segment 1'));
    Segment::createOrUpdate(array('name' => 'Segment 2'));
  }

  function testItCanGetAForm() {
    $form = Form::where('name', 'Form 1')->findOne();

    $router = new Forms();

    $response = $router->get(/* missing id */);
    expect($response)->false();

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get($form->id);
    expect($response['id'])->equals($form->id);
    expect($response['name'])->equals($form->name);
  }

  function testItCanGetListingData() {
    $router = new Forms();
    $response = $router->listing();
    expect($response)->hasKey('filters');
    expect($response)->hasKey('groups');
    expect($response['count'])->equals(3);
    expect($response['items'])->count(3);
    expect($response['items'][0]['name'])->equals('Form 1');
    expect($response['items'][1]['name'])->equals('Form 2');
    expect($response['items'][2]['name'])->equals('Form 3');
  }

  function testItCanCreateANewForm() {
    $router = new Forms();
    $response = $router->create();
    expect($response['result'])->true();
    expect($response['form_id'] > 0)->true();
    expect($response)->hasntKey('errors');

    $created_form = Form::findOne($response['form_id']);
    expect($created_form->name)->equals('New form');
  }

  function testItCanSaveAForm() {
    $form_data = array(
      'name' => 'My first form'
    );

    $router = new Forms();
    $response = $router->save(/* missing data */);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a name');

    $response = $router->save($form_data);
    expect($response['result'])->true();
    expect($response['form_id'] > 0)->true();

    $form = Form::where('name', 'My first form')->findOne();
    expect($form->id)->equals($response['form_id']);
    expect($form->name)->equals('My first form');
  }

  function testItCanPreviewAForm() {
    $router = new Forms();

    $response = $router->create();
    expect($response['result'])->true();
    expect($response['form_id'] > 0)->true();

    $form = Form::findOne($response['form_id']);
    $response = $router->previewEditor($form->asArray());
    expect($response['html'])->notEmpty();
    expect($response['css'])->notEmpty();
  }

  function testItCanExportAForm() {
    $router = new Forms();

    $response = $router->create();
    expect($response['result'])->true();
    expect($response['form_id'] > 0)->true();

    $response = $router->exportsEditor($response['form_id']);
    expect($response['html'])->notEmpty();
    expect($response['php'])->notEmpty();
    expect($response['iframe'])->notEmpty();
    expect($response['shortcode'])->notEmpty();
  }

  function testItCanSaveFormEditor() {
    $router = new Forms();

    $response = $router->create();
    expect($response['result'])->true();
    expect($response['form_id'] > 0)->true();

    $form = Form::findOne($response['form_id'])->asArray();
    $form['name'] = 'Updated form';

    $response = $router->saveEditor($form);
    expect($response['result'])->true();
    expect($response['is_widget'])->false();

    $saved_form = Form::findOne($form['id']);
    expect($saved_form->name)->equals('Updated form');
  }

  function testItCanRestoreAForm() {
    $form = Form::where('name', 'Form 1')->findOne();
    $form->trash();

    $trashed_form = Form::findOne($form->id);
    expect($trashed_form->deleted_at)->notNull();

    $router = new Forms();
    $response = $router->restore($form->id);
    expect($response)->true();

    $restored_form = Form::findOne($form->id);
    expect($restored_form->deleted_at)->null();
  }

  function testItCanTrashAForm() {
    $form = Form::where('name', 'Form 1')->findOne();
    expect($form->deleted_at)->null();

    $router = new Forms();
    $response = $router->trash($form->id);
    expect($response)->true();

    $trashed_form = Form::findOne($form->id);
    expect($trashed_form->deleted_at)->notNull();
  }

  function testItCanDeleteAForm() {
    $form = Form::where('name', 'Form 2')->findOne();
    expect($form->deleted_at)->null();

    $router = new Forms();
    $response = $router->delete($form->id);
    expect($response)->equals(1);

    $deleted_form = Form::findOne($form->id);
    expect($deleted_form)->false();
  }

  function testItCanDuplicateAForm() {
    $form = Form::where('name', 'Form 3')->findOne();

    $router = new Forms();
    $response = $router->duplicate($form->id);
    expect($response['name'])->equals('Copy of '.$form->name);

    $duplicated_form = Form::findOne($response['id']);
    expect($duplicated_form->name)->equals('Copy of '.$form->name);
  }

  function testItCanBulkDeleteForms() {
    expect(Form::count())->equals(3);

    $forms = Form::findMany();
    foreach($forms as $form) {
      $form->trash();
    }

    $router = new Forms();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(3);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(0);
  }

  function _after() {
    Form::deleteMany();
    Segment::deleteMany();
  }
}
