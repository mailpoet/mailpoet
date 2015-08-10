<?php
class FormRendererCest {
  public function _before() {
    $this->form_data = array(
      'form' => 1,
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
    // we need an email field
    expect($html)->contains('name="email"');
    // we need a submit button
    expect($html)->contains('type="submit"');
  }

  public function itCanRenderAFormStyles(){
    $css = \MailPoet\Form\Renderer::renderStyles($this->form_data);
    expect($css)->contains('.mailpoet_form {');
  }

  public function itCanRenderExports() {
    $exports = \MailPoet\Form\Util\Export::getAll($this->form_data);
    foreach($exports as $type => $export) {
      expect($export)
        ->equals(\MailPoet\Form\Util\Export::get($type, $this->form_data));
    }
  }
}
