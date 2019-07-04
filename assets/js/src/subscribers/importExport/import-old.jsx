import ReactDOM from 'react-dom';
import React from 'react';
import Backbone from 'backbone';
import _ from 'underscore';
import jQuery from 'jquery';
import 'asyncqueue';
import MailPoet from 'mailpoet';
import Handlebars from 'handlebars';
import Moment from 'moment';
import StepInputValidation from './import/step_input_validation.jsx';
import StepMethodSelection from './import/step_method_selection.jsx';
import StepResults from './import/step_results.jsx';


jQuery(document).ready(() => {

  router.on('route:step_data_manipulation', () => {
    let fillerPosition;

    // define reusable variables
    const nextStepButton = jQuery('#next_step');

    // create a copy of subscribers object for further manipulation
    const subscribers = jQuery.extend(true, {}, window.importData.step_method_selection);
    const subscribersDataTemplate = Handlebars.compile(jQuery('#subscribers_data_template').html());
    const subscribersDataTemplatePartial = Handlebars.compile(jQuery('#subscribers_data_template_partial').html());
    const segmentSelectElement = jQuery('#mailpoet_segments_select');
    const maxRowsToShow = 10;
    const filler = '. . .';
    // create an array of filler data with the same number of
    // elements as in the subscribers' data row
    const fillerArray = Array(...new Array(subscribers.subscribers[0].length))
      .map(String.prototype.valueOf, filler);

    function toggleNextStepButton(condition) {
      const disabled = 'button-disabled';
      if (condition === 'on') {
        nextStepButton.removeClass(disabled);
        return;
      }
      nextStepButton.addClass(disabled);
    }



    nextStepButton.off().on('click', (event) => {
      const columns = {};
      const queue = new jQuery.AsyncQueue();
      let batchNumber = 0;
      const batchSize = 2000;
      const timestamp = Date.now() / 1000;
      const clickImportResults = {
        created: 0,
        updated: 0,
        errors: [],
        segments: [],
      };

      if (jQuery(event.currentTarget).hasClass('button-disabled')) {
        return;
      }
      MailPoet.Modal.loading(true);
      const splitSubscribers = (localSubscribers, size) => (
        localSubscribers.reduce((res, item, index) => {
          if (index % size === 0) {
            res.push([]);
          }
          res[res.length - 1].push(item);
          return res;
        }, [])
      );
      const clickSubscribers = splitSubscribers(
        window.importData.step_method_selection.subscribers,
        batchSize
      );

      _.each(jQuery('select.mailpoet_subscribers_column_data_match'),
        (column, columnIndex) => {
          const columnId = jQuery(column).data('column-id');
          const validationRule = jQuery(column).data('validation-rule');
          if (columnId === 'ignore') {
            return;
          }
          columns[columnId] = { index: columnIndex, validation_rule: validationRule };
        });

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
              segments: segmentSelectElement.val(),
              updateSubscribers: (jQuery(':radio[name="subscriber_update_option"]:checked').val() === 'yes'),
            }),
          }).done((response) => {
            const added = response.data.added_to_segment_with_welcome_notification;
            clickImportResults.created += response.data.created;
            clickImportResults.updated += response.data.updated;
            clickImportResults.segments = response.data.segments;
            clickImportResults.added_to_segment_with_welcome_notification = added;
            addQueue.run();
          }).fail((response) => {
            MailPoet.Modal.loading(false);
            if (response.errors.length > 0) {
              MailPoet.Notice.error(
                response.errors.map(error => error.message),
                { scroll: true }
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
          clickImportResults.errors.length > 0
          && !clickImportResults.updated
          && !clickImportResults.created
        ) {
          MailPoet.Notice.error(_.flatten(clickImportResults.errors));
        } else {
          window.mailpoetSegments = clickImportResults.segments;
          clickImportResults.segments = _.map(segmentSelectElement.select2('data'),
            data => data.name);
          window.importData.step_data_manipulation = clickImportResults;
          enableSegmentSelection(window.mailpoetSegments);
          router.navigate('step_results', { trigger: true });
        }
      });
    });

    filterSubscribers();
    enableSegmentSelection(window.mailpoetSegments);
  });

  router.on('route:step_results', () => {
    if (typeof (window.importData.step_data_manipulation) === 'undefined') {
      router.navigate('step_data_manipulation', { trigger: true });
      return;
    }

    showCurrentStep();

    MailPoet.trackEvent('Subscribers import finished', {
      'Subscribers created': window.importData.step_data_manipulation.created,
      'Subscribers updated': window.importData.step_data_manipulation.updated,
      'MailPoet Free version': window.mailpoet_version,
    });

    const container = document.getElementById('step_results');

    if (container) {
      ReactDOM.render(
        <StepResults
          navigate={router.navigate}
          errors={window.importData.step_data_manipulation.errors}
          createdSubscribers={window.importData.step_data_manipulation.created}
          updatedSubscribers={window.importData.step_data_manipulation.updated}
          segments={window.importData.step_data_manipulation.segments}
          addedToSegmentWithWelcomeNotification={
            window
              .importData
              .step_data_manipulation
              .added_to_segment_with_welcome_notification
          }
        />,
        container
      );
    }

    // reset previous step's data so that coming back to this step is prevented
    window.importData.step_method_selection = undefined;
    window.importData.step_data_manipulation = undefined;
  });

  if (!Backbone.History.started) {
    Backbone.history.start();
  }
});
