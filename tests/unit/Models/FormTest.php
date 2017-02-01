<?php
use MailPoet\Models\Form;

class FormTest extends MailPoetTest {
  function _before() {
    $this->form = Form::createOrUpdate(array(
      'name' => 'My Form'
    ));
  }

  function testItCanBeCreated() {
    expect($this->form->id() > 0)->true();
    expect($this->form->getErrors())->false();
  }

  function testItHasToBeValid() {
    $invalid_form = Form::create();
    $result = $invalid_form->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
  }

  function testItCanBeGrouped() {
    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(1);

    $forms = Form::filter('groupBy', 'trash')->findArray();
    expect($forms)->count(0);

    $this->form->trash();
    $forms = Form::filter('groupBy', 'trash')->findArray();
    expect($forms)->count(1);

    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(0);

    $this->form->restore();
    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(1);
  }

  function testItCanBeSearched() {
    $form = Form::filter('search', 'my F')->findOne();
    expect($form->name)->equals('My Form');
  }

  function testItHasACreatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    expect($form->created_at)->notNull();
  }

  function testItHasAnUpdatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    expect($form->updated_at)
      ->equals($form->created_at);
  }

  function testItUpdatesTheUpdatedAtOnUpdate() {
    $form = Form::findOne($this->form->id);
    $created_at = $form->created_at;

    sleep(1);

    $form->name = 'new name';
    $form->save();

    $updated_form = Form::findOne($form->id);
    expect($updated_form->created_at)->equals($created_at);
    $is_time_updated = (
      $updated_form->updated_at > $updated_form->created_at
    );
    expect($is_time_updated)->true();
  }

  function testItCanCreateOrUpdate() {
    $created_form = Form::createOrUpdate(array(
      'name' => 'Created Form'
    ));
    expect($created_form->id > 0)->true();
    expect($created_form->getErrors())->false();

    $form = Form::findOne($created_form->id);
    expect($form->name)->equals('Created Form');

    $is_updated = Form::createOrUpdate(array(
      'id' => $created_form->id,
      'name' => 'Updated Form'
    ));
    $form = Form::findOne($created_form->id);
    expect($form->name)->equals('Updated Form');
  }

  function testItCanProvideAFieldList() {
    $form = Form::createOrUpdate(array(
      'name' => 'My Form',
      'body' => array(
        array(
          'type' => 'text',
          'id' => 'email',
        ),
        array(
          'type' => 'text',
          'id' => 2,
        ),
        array(
          'type' => 'submit',
          'id' => 'submit',
        )
      )
    ));
    expect($form->getFieldList())->equals(array('email', 'cf_2'));
  }

  function _after() {
    Form::deleteMany();
  }
}
