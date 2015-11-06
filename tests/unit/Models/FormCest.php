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
    expect($this->saved)->equals(true);
  }

  function itHasToBeValid() {
    expect($this->saved)->equals(true);
    $empty_model = Form::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(1);
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
