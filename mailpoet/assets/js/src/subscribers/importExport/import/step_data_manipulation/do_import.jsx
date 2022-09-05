import jQuery from 'jquery';
import _ from 'underscore';
import { MailPoet } from 'mailpoet';
import 'asyncqueue';

export const doImport = (
  subscribersToImport,
  segments,
  newSubscribersStatus,
  existingSubscribersStatus,
  updateExistingSubscribers,
  tags,
  onImportComplete,
) => {
  const columns = {};
  const queue = new jQuery.AsyncQueue();
  let batchNumber = 0;
  const batchSize = 2000;
  const timestamp = Date.now() / 1000;
  const importResult = {
    created: 0,
    updated: 0,
    errors: [],
    segments: [],
    tags: [],
  };

  MailPoet.Modal.loading(true);
  const splitSubscribers = (localSubscribers, size) =>
    localSubscribers.reduce((res, item, index) => {
      if (index % size === 0) {
        res.push([]);
      }
      res[res.length - 1].push(item);
      return res;
    }, []);
  const clickSubscribers = splitSubscribers(subscribersToImport, batchSize);

  _.each(
    jQuery('select.mailpoet_subscribers_column_data_match'),
    (column, columnIndex) => {
      const columnId = jQuery(column).data('column-id');
      if (columnId === 'ignore') {
        return;
      }
      columns[columnId] = { index: columnIndex };
    },
  );

  _.each(clickSubscribers, () => {
    queue.add((addQueue) => {
      addQueue.pause();
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'ImportExport',
        action: 'processImport',
        data: JSON.stringify({
          columns,
          subscribers: clickSubscribers[batchNumber],
          timestamp,
          segments,
          newSubscribersStatus,
          existingSubscribersStatus,
          updateSubscribers: updateExistingSubscribers,
          tags,
        }),
      })
        .done((response) => {
          const added =
            response.data.added_to_segment_with_welcome_notification;
          importResult.created += response.data.created;
          importResult.updated += response.data.updated;
          importResult.segments = response.data.segments;
          importResult.added_to_segment_with_welcome_notification = added;
          addQueue.run();
        })
        .fail((response) => {
          MailPoet.Modal.loading(false);
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map((error) => error.message),
              { scroll: true },
            );
          }
        });
      batchNumber += 1;
    });
  });

  queue.run();

  queue.onComplete(() => {
    MailPoet.Modal.loading(false);
    if (
      importResult.errors.length > 0 &&
      !importResult.updated &&
      !importResult.created
    ) {
      MailPoet.Notice.error(_.flatten(importResult.errors));
    } else {
      importResult.segments = _.map(
        _.filter(importResult.segments, (segment) =>
          segments.includes(segment.id),
        ),
        (data) => data.name,
      );
      onImportComplete(importResult);
    }
  });
};
