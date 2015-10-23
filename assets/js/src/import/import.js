define(
 [
   'backbone',
   'underscore',
   'jquery',
   'mailpoet',
   'handlebars',
   'papaparse'
 ],
 function (
  Backbone,
  _,
  jQuery,
  MailPoet,
  Handlebars,
  Papa
 ) {
   jQuery(document).ready(function () {
     // configure router
     router = new (Backbone.Router.extend({
       routes: {
         '': 'home',
         'step_1': 'step_1',
         'step_2': 'step_2',
         'step_3': 'step_3'
       },
       home: function () {
         this.navigate('step_1', {trigger: true});
       }
     }));

     function show_current_step() {
       MailPoet.Notice.hide();
       MailPoet.Modal.loading(false);
       jQuery('#mailpoet_subscribers_import > div[id^="step_"]').hide();
       jQuery(location.hash).show();
     }

     /*
      *  STEP 1 (upload or copy/paste)
      */
     router.on('route:step_1', function () {
       // render process button for each each method
       var method_process_template = Handlebars.compile(jQuery('#method_process_template').html());
       jQuery('.mailpoet_method_process').html(method_process_template());

       // define reusable variables
       var current_step = jQuery(location.hash),
        select_method = jQuery('#select_method'),
        subscribers_paste_input = jQuery('#paste_input'),
        subscribers_paste_input_placeholder = subscribers_paste_input.data('placeholder').replace(/\\n/g, '\n'),
        subscribers_paste_process = jQuery('#method_paste > div.mailpoet_method_process').find('a.mailpoet_process'),
        subscribers_mailchimp_key = jQuery('#mailchimp_key'),
        subscribers_mailchimp_key_verify = jQuery('#mailchimp_key_verify'),
        subscribers_mailchimp_lists = jQuery('#mailchimp_lists'),
        subscribers_mailchimp_process = jQuery('#method_mailchimp > div.mailpoet_method_process').find('a.mailpoet_process'),
        subscribers_file_local = jQuery('#file_local'),
        subscribers_file_process = jQuery('#method_file > div.mailpoet_method_process').find('a.mailpoet_process');

       // define method change behavior
       select_method.change(function () {
         MailPoet.Notice.hide();
         var available_methods = jQuery(':radio[name="select_method"]'),
          selected_method = available_methods.index(available_methods.filter(':checked'));
         // hide all methods
         current_step.find('.inside').children('div[id^="method_"]').hide();
         // show selected method
         current_step.find('.inside').children('div[id^="method_"]:eq(' + selected_method + ')').show();
       });

       // start step 1
       show_current_step();
     });

     Backbone.history.start();
   });
 }
);