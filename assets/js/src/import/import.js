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

       /*
        *  Paste
        */

       // display placeholder with multilines. there is no CSS solution that would make this possible.
       subscribers_paste_input.attr('value', subscribers_paste_input_placeholder).css('color', "#999");
       subscribers_paste_input.focus(function () {
         if (jQuery(this).val() == subscribers_paste_input_placeholder) {
           // reset the value only if it equals the initial one
           jQuery(this).attr('value', '').css('color', '#222');
         }
       });
       subscribers_paste_input.blur(function () {
         if (jQuery(this).val() == '') {
           jQuery(this).attr('value', subscribers_paste_input_placeholder).css('color', "#999");

         }
       });

       // monitor textarea changes
       subscribers_paste_input.keyup(function () {
         if (this.value.trim() !== '') {
           subscribers_paste_process.closest('table').show();
         }
         else {
           subscribers_paste_process.closest('table').hide();
         }
       });

       // process paste input
       subscribers_paste_process.click(function () {
         MailPoet.Notice.hide();
         // get size of textarea paste in bytes (approximate)
         var paste_size = encodeURI(subscribers_paste_input.val()).split(/%..|./).length - 1;
         if (paste_size > maximum_parse_size) {
           MailPoet.Notice.error(maximum_parse_notice, {static: true});
           return;
         }
         // show loading indicator and give it 20ms to execute before parsing data
         MailPoet.Modal.loading(true);
         setTimeout(function () {
           Papa.parse(subscribers_paste_input.val(), csvParse());
         }, 10);
       });

       /*
        *  CSV file
        */

       subscribers_file_local.change(function () {
         MailPoet.Notice.hide();
         if (this.value.trim() !== '') {
           subscribers_file_process.closest('table').show();
         }
         else {
           subscribers_file_process.closest('table').hide();
         }
       })

       subscribers_file_process.click(function () {

         if (subscribers_file_local.val().trim() !== '') {
           // show loading indicator and give it 20ms to execute before parsing data
           MailPoet.Modal.loading(true);
           setTimeout(function () {
             subscribers_file_local.parse({
               config: csvParse()
             })
           }, 10);
         }
       })

       /*
        *  MailChimp
        */

       subscribers_mailchimp_key.keyup(function () {
         if (this.value.trim() === '' || !/[a-zA-Z0-9]{32}-/.exec(this.value.trim())) {
           // was the key previously verified and the list block is visible?
           if (subscribers_mailchimp_lists.is(':visible')) {
             subscribers_mailchimp_lists.hide();
             subscribers_mailchimp_process.closest('table').hide();
           }
           // remove any leftover errors from previous verification, if any
           jQuery('.mailpoet_mailchimp-key-status').html('').removeClass('mailpoet_mailchimp-ok mailpoet_mailchimp-error');
           subscribers_mailchimp_key_verify.prop('disabled', true);
         }
         else {
           subscribers_mailchimp_key_verify.prop('disabled', false);
         }
       });

       subscribers_mailchimp_key_verify.click(function () {

         // show loading indicator
         MailPoet.Modal.loading(true);

 /*        mailpoet_get_json(
          'subscribers_import_mailchimp.php',
          {
            'api_key': subscribers_mailchimp_key.val(), 'action': 'get_lists'
          },
          function (result) {
            if (result.status === 'success') {
              jQuery('.mailpoet_mailchimp-key-status').html('').removeClass().addClass('mailpoet_mailchimp-key-status mailpoet_mailchimp-ok');
              if (result.data) {
                subscribers_mailchimp_lists.find('input')
                 .select2({
                   data: result.data,
                   width: '20em',
                   dropdownCssClass: 'mailpoet_no-search',
                   placeholder: MailPoetI18n.select,
                   formatSelection: function (item) {
                     return item.name;
                   },
                   formatResult: function (item) {
                     return item.name;
                   },
                   multiple: true
                 })
                 .change(function () {
                   subscribers_mailchimp_process.closest('table').toggle((jQuery(this).select2('val').length) ? true : false);
                 })
                 .trigger('change');
                subscribers_mailchimp_lists.show();
              }
              else {
                jQuery('.mailpoet_mailchimp-key-status').html(MailPoetI18n.noMailChimpLists);
                subscribers_mailchimp_lists.hide();
                subscribers_mailchimp_process.closest('table').hide();
              }
            }
            else {
              MailPoet.Notice.hide();
              MailPoet.Notice.error(interpret_server_message(result.message));
              jQuery('.mailpoet_mailchimp-key-status').removeClass().addClass('mailpoet_mailchimp-key-status mailpoet_mailchimp-error');
              subscribers_mailchimp_lists.hide();
              subscribers_mailchimp_process.closest('table').hide();
            }
            // hide loading indicator
            MailPoet.Modal.loading(false);
          },
          function (result) {
            // hide loading indicator
            MailPoet.Modal.loading(false);
            MailPoet.Notice.error(MailPoetI18n.serverError + result.statusText.toLowerCase() + '.');
          }
         );*/
       });

       subscribers_mailchimp_process.click(function () {
         // show loading indicator
         MailPoet.Modal.loading(true);

 /*        mailpoet_get_json(
          'subscribers_import_mailchimp.php',
          {
            'api_key': subscribers_mailchimp_key.val(),
            'lists': jQuery('#mailchimp_lists_select').select2('val'),
            'action': 'process'
          },
          function (result) {
            if (result.status === 'success') {
              data_container.step_1 = result;
              router.navigate('step_2', {trigger: true});
            }
            else {
              MailPoet.Notice.hide();
              MailPoet.Notice.error(interpret_server_message(result.message));
            }
            // hide loading indicator
            MailPoet.Modal.loading(false);
          },
          function (result) {
            MailPoet.Modal.loading(false);
            MailPoet.Notice.error(MailPoetI18n.serverError + result.statusText.toLowerCase() + '.');
          }
         );
         subscribers_mailchimp_process.prop('disabled', false);*/

       });       
       
     });

     if (!Backbone.History.started) Backbone.history.start();
   });
 }
);