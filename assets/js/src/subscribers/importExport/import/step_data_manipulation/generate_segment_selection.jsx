import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import _ from 'underscore';

export function createSelection(segments, onSelectionChange) {
  const segmentSelectElement = jQuery('select#mailpoet_segments_select');
  if (segmentSelectElement.data('select2')) {
    return;
  }
  segmentSelectElement.html('');
  segmentSelectElement
    .select2({
      data: segments,
      width: '20em',
      templateResult(item) {
        const i = item;
        i.subscriberCount = parseInt(i.subscriberCount, 10);
        return `${i.name} (${i.subscriberCount.toLocaleString()})`;
      },
      templateSelection(item) {
        const i = item;
        i.subscriberCount = parseInt(i.subscriberCount, 10);
        return `${i.name} (${i.subscriberCount.toLocaleString()})`;
      },
    })
    .change((event) => {
      const segmentSelectionNotice = jQuery('[data-id="notice_segmentSelection"]');
      if (!event.currentTarget.value) {
        if (!segmentSelectionNotice.length) {
          MailPoet.Notice.error(MailPoet.I18n.t('segmentSelectionRequired'), {
            static: true,
            scroll: true,
            id: 'notice_segmentSelection',
            hideClose: true,
          });
        }
      } else {
        jQuery('[data-id="notice_segmentSelection"]').remove();
      }
      const data = _.pluck(segmentSelectElement.select2('data'), 'id');
      onSelectionChange(data);
    });
}

export function destroySelection() {
  const segmentSelectElement = jQuery('select#mailpoet_segments_select');
  if (segmentSelectElement.data('select2')) {
    segmentSelectElement
      .select2('destroy');
    segmentSelectElement
      .find('option')
      .remove();
    segmentSelectElement
      .off('select2:unselecting')
      .off('change')
      .off('select2:opening');
  }
}
