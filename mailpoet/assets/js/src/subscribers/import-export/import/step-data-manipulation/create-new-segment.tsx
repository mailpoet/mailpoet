import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';

interface Segment {
  id: string;
  name: string;
  text: string;
  subscriberCount: number;
}

interface CreateSegmentResponse {
  data: {
    id: string;
    name: string;
    description: string;
  };
}

interface ApiErrorResponse {
  errors: Array<{
    error: string;
    message: string;
  }>;
}

export const createNewSegment = (
  onCreateSegment: (segment: Segment) => void,
): void => {
  MailPoet.Modal.popup({
    title: MailPoet.I18n.t('addNewList'),
    template: jQuery('#new_segment_template').html(),
  });

  jQuery('#new_segment_name').on('keypress', (e: JQuery.KeyPressEvent) => {
    if (e.which === 13) {
      jQuery('#new_segment_process').trigger('click');
    }
  });

  jQuery('#new_segment_process').on('click', () => {
    const segmentName: string =
      jQuery('#new_segment_name').val()?.toString().trim() || '';
    const segmentDescription: string =
      jQuery('#new_segment_description').val()?.toString().trim() || '';

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'ImportExport',
      action: 'addSegment',
      data: {
        name: segmentName,
        description: segmentDescription,
      },
    })
      .done((response: CreateSegmentResponse) => {
        onCreateSegment({
          id: response.data.id,
          name: response.data.name,
          text: response.data.name, // Required select2 property
          subscriberCount: 0,
        });

        MailPoet.Modal.close();
      })
      .fail((response: ApiErrorResponse) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.hide();
          MailPoet.Notice.showApiErrorNotice(response, {
            positionAfter: '#new_segment_error_message',
          });
        }
      });
  });

  jQuery('#new_segment_cancel').on('click', () => {
    MailPoet.Modal.close();
  });
};
