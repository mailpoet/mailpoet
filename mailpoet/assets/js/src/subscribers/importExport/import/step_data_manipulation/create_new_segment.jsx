import jQuery from 'jquery';
import MailPoet from 'mailpoet';

export default (onCreateSegment) => {
  MailPoet.Modal.popup({
    title: MailPoet.I18n.t('addNewList'),
    template: jQuery('#new_segment_template').html(),
  });
  jQuery('#new_segment_name').on('keypress', (e) => {
    if (e.which === 13) {
      jQuery('#new_segment_process').trigger('click');
    }
  });
  jQuery('#new_segment_process').on('click', () => {
    const segmentName = jQuery('#new_segment_name').val().trim();
    const segmentDescription = jQuery('#new_segment_description').val().trim();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'ImportExport',
      action: 'addSegment',
      data: {
        name: segmentName,
        description: segmentDescription,
      },
    })
      .done((response) => {
        onCreateSegment({
          id: response.data.id,
          name: response.data.name,
          text: response.data.name, // Required select2 property
          subscriberCount: 0,
        });

        MailPoet.Modal.close();
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.hide();
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { positionAfter: '#new_segment_name' },
          );
        }
      });
  });
  jQuery('#new_segment_cancel').on('click', () => {
    MailPoet.Modal.close();
  });
};
