<?php
use MailPoet\Models\Form;

class FormCest {
  function _before() {
    $this->form = Form::createOrUpdate(array(
      'name' => 'My Form'
    ));
  }

  function itCanBeCreated() {
    expect($this->form->id() > 0)->true();
    expect($this->form->getErrors())->false();
  }

  function itHasToBeValid() {
    $invalid_form = Form::create();
    $result = $invalid_form->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
  }

  function itCanBeGrouped() {
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

  function itCanBeSearched() {
    $form = Form::filter('search', 'my F')->findOne();
    expect($form->name)->equals('My Form');
  }

  function itCanCreateOrUpdate() {
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


  function _after() {
    Form::deleteMany();
  }
}
