import jQuery from 'jquery';
import MailPoet from 'mailpoet';

export default () => {
  jQuery('select.mailpoet_subscribers_column_data_match')
    .select2({
      data: window.mailpoetColumnsSelect2,
      width: '15em',
      templateResult(item) {
        return item.name;
      },
      templateSelection(item) {
        return item.name;
      },
    })
    .on('select2:selecting', (selectEvent) => {
      const selectElement = selectEvent.currentTarget;
      const selectedOptionId = selectEvent.params.args.data.id;
      // CREATE CUSTOM FIELD
      if (selectedOptionId === 'create') {
        selectEvent.preventDefault();
        jQuery(selectElement).select2('close');
        MailPoet.Modal.popup({
          title: MailPoet.I18n.t('addNewField'),
          template: jQuery('#form_template_field_form').html(),
        });
        jQuery('#form_field_new').parsley().on('form:submit', () => {
          // get data
          const data = jQuery('#form_field_new').mailpoetSerializeObject();

          // save custom field
          MailPoet.Ajax.post({
            api_version: window.mailpoet_api_version,
            endpoint: 'customFields',
            action: 'save',
            data,
          }).done((response) => {
            const newColumnData = {
              id: response.data.id,
              name: response.data.name,
              type: response.data.type,
              params: response.data.params,
              custom: true,
            };
            // if this is the first custom column, create an "optgroup"
            if (window.mailpoetColumnsSelect2.length === 2) {
              window.mailpoetColumnsSelect2.push({
                name: MailPoet.I18n.t('userColumns'),
                children: [],
              });
            }
            window.mailpoetColumnsSelect2[2].children.push(newColumnData);
            window.mailpoetColumns.push(newColumnData);
            jQuery('select.mailpoet_subscribers_column_data_match')
              .each(() => {
                jQuery(selectElement)
                  .html('')
                  .select2('destroy')
                  .select2({
                    data: window.mailpoetColumnsSelect2,
                    width: '15em',
                    templateResult(item) {
                      return item.name;
                    },
                    templateSelection(item) {
                      return item.name;
                    },
                  });
              });
            jQuery(selectElement).data('column-id', newColumnData.id);
            // close popup
            MailPoet.Modal.close();
          }).fail((response) => {
            if (response.errors.length > 0) {
              MailPoet.Notice.error(
                response.errors.map((error) => error.message),
                { positionAfter: '#field_name' }
              );
            }
          });
          return false;
        });
      } else {
        // CHANGE COLUMN
        // check for duplicate values in all select options
        jQuery('select.mailpoet_subscribers_column_data_match')
          .each(() => {
            const element = selectElement;
            const elementId = jQuery(element).val();
            // if another column has the same value and it's not an 'ignore',
            // prompt user
            if (elementId === selectedOptionId
              && elementId !== 'ignore') {
              if (confirm(`${MailPoet.I18n.t('selectedValueAlreadyMatched')} ${MailPoet.I18n.t('confirmCorrespondingColumn')}`)) { // eslint-disable-line
                jQuery(element).data('column-id', 'ignore');
              } else {
                selectEvent.preventDefault();
                jQuery(selectElement).select2('close');
              }
            }
          });
      }
    })
    .on('select2:select', (selectEvent) => {
      const selectElement = selectEvent.currentTarget;
      const selectedOptionId = selectEvent.params.data.id;
      jQuery(selectElement).data('column-id', selectedOptionId);
    });
  jQuery.map(
    jQuery('.mailpoet_subscribers_column_data_match'), (element) => {
      const columnId = jQuery(element).data('column-id');
      jQuery(element).val(columnId).trigger('change');
    }
  );
};
