define(
  [
    'underscore',
    'jquery',
    'mailpoet',
    'handlebars'
  ],
 function (
   _,
   jQuery,
   MailPoet,
   Handlebars
 ) {
   if (!jQuery('#mailpoet_subscribers_export').length) {
     return;
   }
   jQuery(document).ready(function () {
     var segmentsContainerElement;
     var subscriberFieldsContainerElement;
     var exportConfirmedOptionElement;
     var groupBySegmentOptionElement;
     var nextStepButton;
     var renderSegmentsAndFields;
     var subscribers_export_template;
     if (!window.exportData.segments) {
       return;
     }
     subscribers_export_template =
      Handlebars.compile(jQuery('#mailpoet_subscribers_export_template').html());

     // render template
     jQuery('#mailpoet_subscribers_export > div.inside').html(subscribers_export_template(window.exportData));

     function toggleNextStepButton(condition) {
       var disabled = 'button-disabled';
       if (condition === 'on') {
         nextStepButton.removeClass(disabled);
       }
       else {
         nextStepButton.addClass(disabled);
       }
     }

     // define reusable variables
     segmentsContainerElement = jQuery('#export_lists');
     subscriberFieldsContainerElement = jQuery('#export_columns');
     exportConfirmedOptionElement = jQuery(':radio[name="option_confirmed"]');
     groupBySegmentOptionElement = jQuery(':checkbox[name="option_group_by_list"]');
     nextStepButton = jQuery('a.mailpoet_export_process');
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
            ? item.name + ' (' + parseInt(item.subscriberCount).toLocaleString() + ')'
            : item.name;
         },
         templateSelection: function (item) {
           return (item.subscriberCount > 0)
            ? item.name + ' (' + parseInt(item.subscriberCount).toLocaleString() + ')'
            : item.name;
         }
       })
       .on('select2:selecting', function (selectEvent) {
         var selectElement = this;
         var selectedOptionId = selectEvent.params.args.data.id;
         var fieldsToExclude = [
           'select',
           'deselect'
         ];
         var allOptions;
         if (_.contains(fieldsToExclude, selectedOptionId)) {
           selectEvent.preventDefault();
           if (selectedOptionId === 'deselect') {
             jQuery(selectElement).val('').trigger('change');
           } else {
             allOptions = [];
             _.each(container.find('option'), function (field) {
               if (!_.contains(fieldsToExclude, field.value)) {
                 allOptions.push(field.value);
               }
             });
             jQuery(selectElement).val(allOptions).trigger('change');
           }
           jQuery(selectElement).select2('close');
         }
       })
       .on('change', function () {
         if ((window.exportData.segments && segmentsContainerElement.select2('data').length && subscriberFieldsContainerElement.select2('data').length)
          ||
          (!window.exportData.segments && subscriberFieldsContainerElement.select2('data').length)
         ) {
           toggleNextStepButton('on');
         }
         else {
           toggleNextStepButton('off');
         }

         if (segmentsContainerElement.select2('data').length > 1 && window.exportData.groupBySegmentOption) {
           jQuery('.mailpoet_group_by_list').show();
         }
         else if (window.exportData.groupBySegmentOption) {
           jQuery('.mailpoet_group_by_list').hide();
         }
       });
     };

     // set confirmed subscribers export option to false
     window.exportData.exportConfirmedOption = false;

     renderSegmentsAndFields(subscriberFieldsContainerElement, window.subscriberFieldsSelect2);
     renderSegmentsAndFields(segmentsContainerElement, window.segments);

     subscriberFieldsContainerElement.val([
       'email',
       'first_name',
       'last_name',
       'status'
     ]).trigger('change');

     exportConfirmedOptionElement.change(function () {
       var selectedSegments = segmentsContainerElement.val();
       if (this.value == 1) {
         window.exportData.exportConfirmedOption = true;
         renderSegmentsAndFields(segmentsContainerElement, window.segmentsWithConfirmedSubscribers);
       }
       else {
         window.exportData.exportConfirmedOption = false;
         renderSegmentsAndFields(segmentsContainerElement, window.segments);
       }
       segmentsContainerElement.val(selectedSegments).trigger('change');
     });

     nextStepButton.click(function () {
       var exportFormat;
       if (jQuery(this).hasClass('button-disabled')) {
         return;
       }
       MailPoet.Modal.loading(true);
       exportFormat = jQuery(':radio[name="option_format"]:checked').val();
       MailPoet.Ajax.post({
         api_version: window.mailpoet_api_version,
         endpoint: 'ImportExport',
         action: 'processExport',
         data: JSON.stringify({
           export_confirmed_option: window.exportData.exportConfirmedOption,
           export_format_option: exportFormat,
           group_by_segment_option: (groupBySegmentOptionElement.is(':visible')) ? groupBySegmentOptionElement.prop('checked') : false,
           segments: (window.exportData.segments) ? segmentsContainerElement.val() : false,
           subscriber_fields: subscriberFieldsContainerElement.val()
         })
       }).always(function () {
         MailPoet.Modal.loading(false);
       }).done(function (response) {
         var resultMessage = MailPoet.I18n.t('exportMessage')
         .replace('%1$s', '<strong>' + parseInt(response.data.totalExported).toLocaleString() + '</strong>')
         .replace('[link]', '<a href="' + response.data.exportFileURL + '" target="_blank" >')
         .replace('[/link]', '</a>');
         jQuery('#export_result_notice').html('<p>' + resultMessage + '</p>').show();
         window.location.href = response.data.exportFileURL;
         MailPoet.trackEvent('Subscribers export completed', {
           'Total exported': response.data.totalExported,
           'Only confirmed?': window.exportData.exportConfirmedOption,
           'File Format': exportFormat,
           'MailPoet Free version': window.mailpoet_version
         });
       }).fail(function (response) {
         if (response.errors.length > 0) {
           MailPoet.Notice.error(
              response.errors.map(function (error) { return error.message; }),
              { scroll: true }
            );
         }
       });
     });
   });
 });
