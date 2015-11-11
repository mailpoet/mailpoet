define(
 [
   'underscore',
   'jquery',
   'mailpoet',
   'handlebars',
   'select2'
 ],
 function (
  _,
  jQuery,
  MailPoet,
  Handlebars
 ) {
   if (!jQuery("#mailpoet_subscribers_export").length) {
     return;
   }
   jQuery(document).ready(function () {
     // halt operation if there are no lists with subscribers found
     if (!exportData.lists) return;

     var subscribers_export_template = Handlebars.compile($('#mailpoet_subscribers_export_template').html());

     //render template
     jQuery('#mailpoet_subscribers_export > div.inside').html(subscribers_export_template(exportData));

     // define reusable variables
     var segmentsContainerElement = jQuery("#export_lists"),
      subscriberFieldsContainerElement = jQuery("#export_columns"),
      exportConfirmedOptionElement = jQuery(':radio[name="option_confirmed"]'),
      groupBySegmentOptionElement = jQuery(':checkbox[name="option_group_by_list"]'),
      nextStepElement = jQuery("a.mailpoet_export_process"),
      renderSegmentsAndFields = function (container, data) {
        if (container.data('select2')) {
          container
           .html('')
           .select2('destroy');
        }
        container
         .select2({
           data: data,
           width: '20em',
           templateResult: function (item) {
             return (item.subscriberCount > 0)
              ? item.name + ' (' + item.subscriberCount + ')'
              : item.name;
           },
           templateSelection: function (item) {
             return (item.subscriberCount > 0)
              ? item.name + ' (' + item.subscriberCount + ')'
              : item.name;
           }
         })
         .on('select2:selecting', function (selectEvent) {
           var selectElement = this,
            selectedOptionId = selectEvent.params.args.data.id;

         });
/*         .change(function (event) {
           var trigger_element = this;

           if (event.added && event.added.id === 'deselect_all') jQuery(trigger_element).select2('val', '');
           if (event.added && event.added.id === 'select_all') jQuery(trigger_element).select2('val', columns_ids);

           if ((exportData.lists && segmentsContainerElement.select2('data').length && subscriberFieldsContainerElement.select2('data').length)
            ||
            (!exportData.lists && subscriberFieldsContainerElement.select2('data').length)
           ) {
             jQuery(nextStepElement).removeClass('button-disabled');
           }
           else {
             jQuery(nextStepElement).addClass('button-disabled');
           }

           if (segmentsContainerElement.select2('data').length > 1 && exportData.group_by_list) {
             jQuery('.mailpoet_group_by_list').show();
           }
           else if (exportData.group_by_list) jQuery('.mailpoet_group_by_list').hide();
         })*/
      };

     // render lists and columns
     renderSegmentsAndFields(subscriberFieldsContainerElement, columns);
     renderSegmentsAndFields(segmentsContainerElement, lists);

     // pre-select columns
   //  subscriberFieldsContainerElement.select2('val', ['subscriber_state', 'subscriber_email', 'subscriber_firstname', 'subscriber_lastname']);

     // handle confirmed/unconfirmed behavior
     exportConfirmedOptionElement.change(function () {
       if (this.value == 1) {
         exportData.confirmed_option = true;
         renderSegmentsAndFields(segmentsContainerElement, lists_confirmed);
       }
       else {
         exportData.confirmed_option = false;
         renderSegmentsAndFields(segmentsContainerElement, lists);
       }
     });
/*
     // process export
     nextStepElement.click(function () {
       if (jQuery(this).hasClass('button-disabled')) return;
       MailPoet.Modal.loading(true);
       mailpoet_post_json(
        'subscribers_export.php',
        {
          'option_confirmed': exportData.confirmed_option,
          'option_format': jQuery(':radio[name="option_format"]:checked').val(),
          'option_group_by_list': (groupBySegmentOptionElement.is(":visible")) ? groupBySegmentOptionElement.prop('checked') : false,
          'lists': (exportData.lists) ? segmentsContainerElement.select2('val') : false,
          'columns': subscriberFieldsContainerElement.select2('data'),
          'export_directory': export_directory
        },
        function (response) {
          if (response.result === true) {
            response.message = "<?php _e('%1$s subscribers were exported. Get the exported file [link]here[/link].'); ?>"
             .replace('%1$s', '<strong>' + response.total + '</strong>')
             .replace('[link]', '<a href="' + export_url + response.filename + '" target="_blank" >')
             .replace('[/link]', '</a>')
            jQuery('#export_result_notice > ul > li').html(response.message)
            jQuery('#export_result_notice').show();
            window.location.href = export_url + response.filename;
          }
          else {
            MailPoet.Notice.error(response.message);
          }
          MailPoet.Modal.loading(false);
        },
        function (response) {
          MailPoet.Modal.loading(false);
          MailPoet.Notice.error('<?php _e("Server error:"); ?> ' + response.statusText.toLowerCase() + '.', {scroll: true});
        }
       )
     })*/
   });
 });