<?php
use MailPoet\Models\Form;

class FormCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'my form',
    );

    $this->form = Form::create();
    $this->form->hydrate($this->data);
    $this->saved = $this->form->save();
  }

  function itCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function itHasToBeValid() {
    $invalid_form = Form::create();
    $result = $invalid_form->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
  }

  function itHasACreatedAtOnCreation() {
    $form = Form::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($form->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $form = Form::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($form->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $form = Form::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $form->created_at;
    $form->name = 'new name';
    $form->save();
    expect($old_created_at)->equals($form->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $form = Form::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $form->name = 'new name';
    $form->save();
    $time_difference = strtotime($form->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function itCanCreateOrUpdate() {
    $is_created = Form::createOrUpdate(array(
      'name' => 'new form'
    ));
    expect($is_created)->notEquals(false);
    expect($is_created->getValidationErrors())->isEmpty();

    $form = Form::where('name', 'new form')->findOne();
    expect($form->name)->equals('new form');

    $is_updated = Form::createOrUpdate(array(
      'id' => $form->id,
      'name' => 'updated form'
    ));
    $form = Form::where('name', 'updated form')->findOne();
    expect($form->name)->equals('updated form');
  }


  function _after() {
    ORM::forTable(Form::$_table)
      ->deleteMany();
  }
}
