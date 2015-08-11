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
            'field' => 'subscriber_email',
            'static' => true,
            'params' => array(
              'label' => __('Email'),
              'required' => true
            )
          ),
          array(
            'name' => __('Checkbox'),
            'type' => 'checkbox',
            'field' => 'subscriber_checkbox',
            'params' => array(
              'label' => __('Checkbox'),
              'values' => array(
                array('is_checked' => true, 'value' => 'checkbox value 1'),
                array('is_checked' => true, 'value' => 'checkbox value 2'),
                array('is_checked' => true, 'value' => 'checkbox value 3')
              )
            )
          ),
          array(
            'name' => __('Date'),
            'type' => 'date',
            'field' => 'subscriber_date',
            'params' => array(
              'label' => __('Date'),
              'required' => true,
              'date_type' => 'year_month_day'
            )
          ),
          array(
            'type' => 'divider'
          ),
          array(
            'name' => __('Html'),
            'type' => 'html',
            'field' => 'subscriber_html',
            'params' => array(
              'text' => "This is <strong>HTML</strong>\nNew line",
              'nl2br' => true
            )
          ),
          array(
            'name' => __('Input'),
            'type' => 'input',
            'field' => 'subscriber_input',
            'params' => array(
              'label' => __('Input')
            )
          ),
          array(
            'name' => __('Lists'),
            'type' => 'list',
            'field' => 'subscriber_list',
            'params' => array(
              'label' => __('Lists'),
              'values' => array(
                array('id' => 1, 'name' => 'list 1'),
                array('id' => 2, 'name' => 'list 2'),
                array('id' => 3, 'name' => 'list 3')
              )
            )
          ),
          array(
            'name' => __('Radio'),
            'type' => 'radio',
            'field' => 'subscriber_radio',
            'params' => array(
              'label' => __('Radio'),
              'values' => array(
                array('is_checked' => true, 'value' => 'radio value 1'),
                array('is_checked' => true, 'value' => 'radio value 2'),
                array('is_checked' => true, 'value' => 'radio value 3')
              )
            )
          ),
          array(
            'name' => __('Select'),
            'type' => 'select',
            'field' => 'subscriber_select',
            'static' => true,
            'params' => array(
              'label' => __('Select'),
              'values' => array(
                array('is_checked' => true, 'value' => 'select value 1'),
                array('is_checked' => true, 'value' => 'select value 2'),
                array('is_checked' => true, 'value' => 'select value 3')
              )
            )
          ),
          array(
            'name' => __('Textarea'),
            'type' => 'submit',
            'field' => 'subscriber_submit',
            'static' => true,
            'params' => array(
              'text' => "This is <strong>HTML</strong>\nNew line",
              'lines' => 3,
              'nl2br' => true
            )
          ),
          array(
            'name' => __('Submit'),
            'type' => 'submit',
            'field' => 'subscriber_submit',
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

    // email
    expect($html)->contains('name="subscriber_email"');
    // date
    expect($html)->contains('class="mailpoet_date_day"');
    expect($html)->contains('name="subscriber_date[day]"');
    expect($html)->contains('class="mailpoet_date_month"');
    expect($html)->contains('name="subscriber_date[month]"');
    expect($html)->contains('class="mailpoet_date_year"');
    expect($html)->contains('name="subscriber_date[year]"');
    // radio
    expect($html)->contains('class="mailpoet_radio"');
    expect($html)->contains('name="subscriber_radio"');
    // checkbox
    expect($html)->contains('class="mailpoet_checkbox"');
    expect($html)->contains('name="subscriber_checkbox"');
    // divider
    expect($html)->contains(\MailPoet\Form\Block\Divider::render());
    // html
    expect($html)->contains("This is <strong>HTML</strong><br />\nNew line");
    // input
    expect($html)->contains('class="mailpoet_input"');
    expect($html)->contains('name="subscriber_input"');
    // list
    expect($html)->contains('name="subscriber_list"');
    // submit
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
