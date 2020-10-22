import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import Handlebars from 'handlebars';

jQuery(document).ready(function documentReady() {
  var segmentsContainerElement;
  var subscriberFieldsContainerElement;
  var nextStepButton;
  var renderSegmentsAndFields;
  var subscribersExportTemplate;
  if (!jQuery('#mailpoet_subscribers_export').length) {
    return;
  }
  if (!window.exportData.segments) {
    return;
  }
  subscribersExportTemplate = Handlebars.compile(jQuery('#mailpoet_subscribers_export_template').html());

  // render template
  jQuery('#mailpoet-export').html(subscribersExportTemplate(window.exportData));

  function toggleNextStepButton(condition) {
    var disabled = 'mailpoet-disabled';
    if (condition === 'on') {
      nextStepButton.removeClass(disabled);
    } else {
      nextStepButton.addClass(disabled);
    }
  }

  // define reusable variables
  segmentsContainerElement = jQuery('#export_lists');
  subscriberFieldsContainerElement = jQuery('#export_columns');
  nextStepButton = jQuery('#mailpoet-export-button');
  renderSegmentsAndFields = function renderSegmentsFields(container, data) {
    if (container.data('select2')) {
      container
        .html('')
        .select2('destroy');
    }
    container
      .select2({
        data: data,
        width: '20em',
        templateResult: function templateResult(item) {
          return (item.subscriberCount > 0)
            ? item.name + ' (' + parseInt(item.subscriberCount, 10).toLocaleString() + ')'
            : item.name;
        },
        templateSelection: function templateSelection(item) {
          return (item.subscriberCount > 0)
            ? item.name + ' (' + parseInt(item.subscriberCount, 10).toLocaleString() + ')'
            : item.name;
        },
      })
      .on('select2:selecting', function onSelect2Selecting(selectEvent) {
        var selectElement = this;
        var selectedOptionId = selectEvent.params.args.data.id;
        var fieldsToExclude = [
          'select',
          'deselect',
        ];
        var allOptions;
        if (_.contains(fieldsToExclude, selectedOptionId)) {
          selectEvent.preventDefault();
          if (selectedOptionId === 'deselect') {
            jQuery(selectElement).val('').trigger('change');
          } else {
            allOptions = [];
            _.each(container.find('option'), function eachOption(field) {
              if (!_.contains(fieldsToExclude, field.value)) {
                allOptions.push(field.value);
              }
            });
            jQuery(selectElement).val(allOptions).trigger('change');
          }
          jQuery(selectElement).select2('close');
        }
      })
      .on('change', function onChange() {
        if ((window.exportData.segments && segmentsContainerElement.select2('data').length && subscriberFieldsContainerElement.select2('data').length)
          || (!window.exportData.segments && subscriberFieldsContainerElement.select2('data').length)
        ) {
          toggleNextStepButton('on');
        } else {
          toggleNextStepButton('off');
        }
      });
  };

  window.segments.forEach(function createSegmentOption(item) {
    segmentsContainerElement.append(jQuery('<option></option>').attr('value', item.id).text(item.name));
  });
  renderSegmentsAndFields(segmentsContainerElement, window.segments);
  renderSegmentsAndFields(subscriberFieldsContainerElement, window.subscriberFieldsSelect2);

  subscriberFieldsContainerElement.val([
    'email',
    'first_name',
    'last_name',
    'list_status',
    'global_status',
  ]).trigger('change');

  nextStepButton.click(function nextClick() {
    var exportFormat;
    if (jQuery(this).hasClass('mailpoet-disabled')) {
      return;
    }
    MailPoet.Modal.loading(true);
    exportFormat = jQuery(':radio[name="option_format"]:checked').val();
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'ImportExport',
      action: 'processExport',
      data: JSON.stringify({
        export_format_option: exportFormat,
        segments: (window.exportData.segments) ? segmentsContainerElement.val() : false,
        subscriber_fields: subscriberFieldsContainerElement.val(),
      }),
    }).always(function always() {
      MailPoet.Modal.loading(false);
    }).done(function done(response) {
      var resultMessage = MailPoet.I18n.t('exportMessage')
        .replace('%1$s', '<strong>' + parseInt(response.data.totalExported, 10).toLocaleString() + '</strong>')
        .replace('[link]', '<a href="' + response.data.exportFileURL + '" target="_blank" >')
        .replace('[/link]', '</a>');
      jQuery('#export_result_notice').html('<p>' + resultMessage + '</p>').show();
      window.location.href = response.data.exportFileURL;
      MailPoet.trackEvent('Subscribers export completed', {
        'Total exported': response.data.totalExported,
        'File Format': exportFormat,
        'MailPoet Free version': window.mailpoet_version,
      });
    }).fail(function fail(response) {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function mapError(error) { return error.message; }),
          { scroll: true }
        );
      }
    });
  });
});
