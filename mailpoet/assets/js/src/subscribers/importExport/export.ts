import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import Handlebars from 'handlebars';
import { escapeHTML } from '@wordpress/escape-html';

interface ExportWindow extends Window {
  exportData: {
    segments: number | null;
  };
  segments: Array<{
    id: string;
    name: string;
    count: number;
  }>;
  subscriberFieldsSelect2: Array<{
    name: string;
    text: string; // Required select2 property
    children: Array<{
      id: string;
      name: string;
      text: string; // Required select2 property
      type: string | null;
      custom: boolean;
      params: Record<string, unknown>;
    }>;
  }>;
}

declare let window: ExportWindow;

jQuery(document).ready(() => {
  let nextStepButton;
  if (!jQuery('#mailpoet_subscribers_export').length) {
    return;
  }
  if (!window.exportData.segments) {
    return;
  }
  const subscribersExportTemplate = Handlebars.compile(
    jQuery('#mailpoet_subscribers_export_template').html(),
  );

  // render template
  jQuery('#mailpoet-export').html(subscribersExportTemplate(window.exportData));

  function toggleNextStepButton(condition): void {
    const disabled = 'mailpoet-disabled';
    if (condition === 'on') {
      nextStepButton.removeClass(disabled);
    } else {
      nextStepButton.addClass(disabled);
    }
  }

  // define reusable variables
  const segmentsContainerElement = jQuery('#export_lists');
  const subscriberFieldsContainerElement = jQuery('#export_columns');
  nextStepButton = jQuery('#mailpoet-export-button');

  function templateRendered(option): string {
    let tpl = `<span class="mailpoet-form-select2-text"><span>${option.name}</span></span>`;
    if (option.count !== undefined) {
      tpl += `<span class="mailpoet-form-select2-count">${option.count}</span>`;
    }
    return tpl;
  }

  function renderSegmentsAndFields(container, data): void {
    if (container.data('select2')) {
      container.html('').select2('destroy');
    }
    container
      .select2({
        data,
        width: '20em',
        dropdownCssClass: 'mailpoet-form-select2-dropdown',
        escapeMarkup: (markup) => markup,
        templateResult: templateRendered,
        templateSelection: templateRendered,
      })
      .on('select2:selecting', (selectEvent) => {
        const selectedOptionId = selectEvent.params.args.data.id;
        const fieldsToExclude = ['select', 'deselect'];
        let allOptions: string[];
        if (_.contains(fieldsToExclude, selectedOptionId)) {
          selectEvent.preventDefault();
          if (selectedOptionId === 'deselect') {
            jQuery(selectEvent.target).val('').trigger('change');
          } else {
            allOptions = [];
            _.each(container.find('option'), (field) => {
              const value: string = field.value;
              if (!_.contains(fieldsToExclude, value)) {
                allOptions.push(value);
              }
            });
            jQuery(selectEvent.target).val(allOptions).trigger('change');
          }
          jQuery(selectEvent.target).select2('close');
        }
      })
      .on('change', () => {
        if (
          (window.exportData.segments &&
            segmentsContainerElement.select2('data').length &&
            subscriberFieldsContainerElement.select2('data').length) ||
          (!window.exportData.segments &&
            subscriberFieldsContainerElement.select2('data').length)
        ) {
          toggleNextStepButton('on');
        } else {
          toggleNextStepButton('off');
        }
      });
  }

  window.segments.forEach((item) => {
    segmentsContainerElement.append(
      jQuery('<option></option>').attr('value', item.id).text(item.name),
    );
  });
  // Select2 requires the property text, then we fill it with name
  window.subscriberFieldsSelect2.forEach((group) => {
    group.text = group.name; // eslint-disable-line no-param-reassign
    group.children.forEach((item) => {
      item.name = escapeHTML(item.name); // eslint-disable-line no-param-reassign
      item.text = escapeHTML(item.name); // eslint-disable-line no-param-reassign
    });
  });
  renderSegmentsAndFields(segmentsContainerElement, window.segments);
  renderSegmentsAndFields(
    subscriberFieldsContainerElement,
    window.subscriberFieldsSelect2,
  );

  subscriberFieldsContainerElement
    .val([
      'email',
      'first_name',
      'last_name',
      'list_status',
      'global_status',
      'subscribed_ip',
      'created_at',
      'confirmed_at',
      'confirmed_ip',
    ])
    .trigger('change');

  nextStepButton.on('click', (event) => {
    if (jQuery(event.target).hasClass('mailpoet-disabled')) {
      return;
    }
    MailPoet.Modal.loading(true);
    const exportFormat = jQuery(':radio[name="option_format"]:checked').val();
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'ImportExport',
      action: 'processExport',
      data: JSON.stringify({
        export_format_option: exportFormat,
        segments: window.exportData.segments
          ? segmentsContainerElement.val()
          : false,
        subscriber_fields: subscriberFieldsContainerElement.val(),
      }),
    })
      .always(() => {
        MailPoet.Modal.loading(false);
      })
      .done((response) => {
        const resultMessage = MailPoet.I18n.t('exportMessage')
          .replace(
            '%1$s',
            `<strong>${parseInt(
              response.data.totalExported as string,
              10,
            ).toLocaleString()}</strong>`,
          )
          .replace(
            '[link]',
            `<a href="${response.data.exportFileURL}" target="_blank" >`,
          )
          .replace('[/link]', '</a>');
        jQuery('#export_result_notice').html(`<p>${resultMessage}</p>`).show();
        window.location.href = response.data.exportFileURL;
        MailPoet.trackEvent('Subscribers export completed', {
          'Total exported': response.data.totalExported,
          'File Format': exportFormat,
        });
      })
      .fail((response: ErrorResponse) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
        }
      });
  });
});
