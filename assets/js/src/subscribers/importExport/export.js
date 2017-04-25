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
  Handlebars,
  select2
 ) {
   if (!jQuery("#mailpoet_subscribers_export").length) {
     return;
   }
   jQuery(document).ready(function () {
     if (!exportData.segments) {
       return;
     }
     var subscribers_export_template =
      Handlebars.compile(jQuery('#mailpoet_subscribers_export_template').html());

     //render template
     jQuery('#mailpoet_subscribers_export > div.inside').html(subscribers_export_template(exportData));

     // define reusable variables
     var segmentsContainerElement = jQuery("#export_lists"),
      subscriberFieldsContainerElement = jQuery("#export_columns"),
      exportConfirmedOptionElement = jQuery(':radio[name="option_confirmed"]'),
      groupBySegmentOptionElement = jQuery(':checkbox[name="option_group_by_list"]'),
      nextStepButton = jQuery("a.mailpoet_export_process"),
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
           var selectElement = this,
            selectedOptionId = selectEvent.params.args.data.id,
            fieldsToExclude = [
              'select',
              'deselect'
            ];
           if (_.contains(fieldsToExclude, selectedOptionId)) {
             selectEvent.preventDefault();
             if (selectedOptionId === 'deselect') {
               jQuery(selectElement).val('').trigger('change');
             } else {
               var allOptions = [];
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
           if ((exportData.segments && segmentsContainerElement.select2('data').length && subscriberFieldsContainerElement.select2('data').length)
            ||
            (!exportData.segments && subscriberFieldsContainerElement.select2('data').length)
           ) {
             toggleNextStepButton('on');
           }
           else {
             toggleNextStepButton('off');
           }

           if (segmentsContainerElement.select2('data').length > 1 && exportData.groupBySegmentOption) {
             jQuery('.mailpoet_group_by_list').show();
           }
           else if (exportData.groupBySegmentOption) {
             jQuery('.mailpoet_group_by_list').hide();
           }
         });
      };

     // set confirmed subscribers export option to false
     exportData.exportConfirmedOption = false;

     renderSegmentsAndFields(subscriberFieldsContainerElement, subscriberFieldsSelect2);
     renderSegmentsAndFields(segmentsContainerElement, segments);

     subscriberFieldsContainerElement.val([
       'email',
       'first_name',
       'last_name',
       'status'
     ]).trigger("change");

     exportConfirmedOptionElement.change(function () {
       var selectedSegments = segmentsContainerElement.val();
       if (this.value == 1) {
         exportData.exportConfirmedOption = true;
         renderSegmentsAndFields(segmentsContainerElement, segmentsWithConfirmedSubscribers);
       }
       else {
         exportData.exportConfirmedOption = false;
         renderSegmentsAndFields(segmentsContainerElement, segments);
       }
       segmentsContainerElement.val(selectedSegments).trigger('change');
     });

     function toggleNextStepButton(condition) {
       var disabled = 'button-disabled';
       if (condition === 'on') {
         nextStepButton.removeClass(disabled);
       }
       else {
         nextStepButton.addClass(disabled);
       }
     }

     nextStepButton.click(function () {
       if (jQuery(this).hasClass('button-disabled')) {
         return;
       }
       MailPoet.Modal.loading(true);
       MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'ImportExport',
          action: 'processExport',
          data: JSON.stringify({
            'export_confirmed_option': exportData.exportConfirmedOption,
            'export_format_option': jQuery(':radio[name="option_format"]:checked').val(),
            'group_by_segment_option': (groupBySegmentOptionElement.is(":visible")) ? groupBySegmentOptionElement.prop('checked') : false,
            'segments': (exportData.segments) ? segmentsContainerElement.val() : false,
            'subscriber_fields': subscriberFieldsContainerElement.val()
          })
        }).always(function(response) {
          MailPoet.Modal.loading(false);
        }).done(function(response) {
          resultMessage = MailPoet.I18n.t('exportMessage')
           .replace('%1$s', '<strong>' + parseInt(response.data.totalExported).toLocaleString() + '</strong>')
           .replace('[link]', '<a href="' + response.data.exportFileURL + '" target="_blank" >')
           .replace('[/link]', '</a>');
          jQuery('#export_result_notice').html('<p>' + resultMessage + '</p>').show();
          window.location.href = response.data.exportFileURL;
        }).fail(function(response) {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(function(error) { return error.message; }),
              { scroll: true }
            );
          }
        });
     });
   });
 });
