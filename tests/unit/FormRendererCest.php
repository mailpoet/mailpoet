<?php

class FormRendererCest {
  public function _before() {
    $this->form_data = array(
      'form_name' => __("New form"),
      'form_created_at' => time(),
      'data' => array(
        'settings' => array(
          'on_success' => 'message',
          'success_message' => __('Check your inbox or spam folder now to confirm your subscription.'),
          'lists' => null,
          'lists_selected_by' => 'admin'
          ),
        'body' => array(
          array(
            'name' => __('Email'),
            'type' => 'input',
            'field' => 'email',
            'static' => true,
            'params' => array(
              'label' => __('Email'),
              'required' => true
              )
            ),
          array(
            'name' => __('Submit'),
            'type' => 'submit',
            'field' => 'submit',
            'static' => true,
            'params' => array(
              'label' => __('Subscribe!')
            )
          )
        )
      )
    );
  }

    // tests
  public function itCanRenderAForm(){
    $html = \MailPoet\Form\Renderer::render($this->form_data);
    expect($html)->contains('Email');
    expect($html)->contains('Subscribe!');
  }

  public function itCanRenderAFormStyles(){
    $css = \MailPoet\Form\Renderer::renderStyles($this->form_data);
    expect($css)->contains('.mailpoet_form {');
  }
}
