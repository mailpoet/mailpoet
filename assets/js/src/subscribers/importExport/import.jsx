import ReactDOM from 'react-dom';
import React from 'react';
import Backbone from 'backbone';
import _ from 'underscore';
import jQuery from 'jquery';
import 'asyncqueue';
import MailPoet from 'mailpoet';
import Handlebars from 'handlebars';
import Papa from 'papaparse';
import Moment from 'moment';
import sanitizeCSVData from './sanitize_csv_data.jsx';
import StepInputValidation from './step_input_validation.jsx';

const SUBSCRIBERS_LIMIT_FOR_VALIDATION = 500;

function getMethodSelectionNextStepLink(importData) {
  if (importData === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount < SUBSCRIBERS_LIMIT_FOR_VALIDATION) {
    return 'step_data_manipulation';
  }
  return 'step_input_validation';
}

function getDataManipulationPreviousStepLink(importData) {
  if (importData === undefined) {
    return 'step_method_selection';
  }
  if (importData.subscribersCount === undefined) {
    return 'step_method_selection';
  }
  if (importData.subscribersCount < SUBSCRIBERS_LIMIT_FOR_VALIDATION) {
    return 'step_method_selection';
  }
  return 'step_input_validation';
}

jQuery(document).ready(() => {
  if (!jQuery('#mailpoet_subscribers_import').length) {
    return;
  }
  jQuery('input[name="select_method"]').attr('checked', false);
  // configure router
  const router = new (Backbone.Router.extend({
    routes: {
      '': 'home',
      step_method_selection: 'step_method_selection',
      step_input_validation: 'step_input_validation',
      step_data_manipulation: 'step_data_manipulation',
      step_results: 'step_results',
    },
    home() {
      this.navigate('step_method_selection', { trigger: true });
    },
  }))();

  function showCurrentStep() {
    MailPoet.Notice.hide();
    MailPoet.Modal.loading(false);
    jQuery('#mailpoet_subscribers_import > div[id^="step"]').hide();
    jQuery(window.location.hash).show();
  }

  router.on('route:step_method_selection', () => {
    // set or reset temporary validation rule on all columns
    window.mailpoetColumns = jQuery.map(window.mailpoetColumns, (column) => {
      const col = column;
      col.validation_rule = false;
      return col;
    });

    if (typeof (window.importData.step_method_selection) !== 'undefined') {
      showCurrentStep();
      return;
    }

    // render process button for each method
    const methodProcessContainerTemplate = Handlebars.compile(jQuery('#method_process_template').html());
    jQuery('.mailpoet_method_process').html(methodProcessContainerTemplate());

    // define reusable variables
    const currentStepE = jQuery(window.location.hash);
    const methodSelectionElement = jQuery('#select_method');
    const pasteInputElement = jQuery('#paste_input');
    const pasteInputPlaceholderElement = pasteInputElement.data('placeholder').replace(/\\n/g, '\n');
    const pasteProcessButtonElement = jQuery('#method_paste > div.mailpoet_method_process')
      .find('a.mailpoet_process');
    const mailChimpKeyInputElement = jQuery('#mailchimp_key');
    const mailChimpKeyVerifyButtonElement = jQuery('#mailchimp_key_verify');
    const mailChimpListsContainerElement = jQuery('#mailchimp_lists');
    const mailChimpProcessButtonElement = jQuery('#method_mailchimp > div.mailpoet_method_process')
      .find('a.mailpoet_process');
    const uploadElement = jQuery('#file_local');
    const uploadProcessButtonElement = jQuery('#method_file > div.mailpoet_method_process')
      .find('a.mailpoet_process');

    // define method change behavior
    methodSelectionElement.change(() => {
      const availableMethods = jQuery(':radio[name="select_method"]');
      const selectedMethod = availableMethods.index(availableMethods.filter(':checked'));
      MailPoet.Notice.hide();
      // hide all methods
      currentStepE.find('.inside')
        .children('div[id^="method_"]')
        .hide();
      // show selected method
      currentStepE.find('.inside')
        .children(`div[id^="method_"]:eq(${selectedMethod})`)
        .show()
        .find('table')
        .show();
    });

    // start step 1
    showCurrentStep();

    function toggleNextStepButton(element, condition) {
      const disabled = 'button-disabled';
      if (condition === 'on') {
        element.closest('table a').removeClass(disabled);
        return;
      }
      element.closest('table a').addClass(disabled);
    }

    function papaParserConfig(isFile) {
      return {
        skipEmptyLines: true,
        error() {
          MailPoet.Notice.hide();
          MailPoet.Notice.error(MailPoet.I18n.t('dataProcessingError'));
        },
        complete(CSV) {
          const sanitizedData = sanitizeCSVData(CSV.data);
          if (sanitizedData) {
            // since we assume that the header line is always present, we need
            // to detect the header by checking if it contains a valid e-mail address
            window.importData.step_method_selection = sanitizedData;
            MailPoet.trackEvent('Subscribers import started', {
              source: isFile ? 'file upload' : 'pasted data',
              'MailPoet Free version': window.mailpoet_version,
            });
            router.navigate(
              getMethodSelectionNextStepLink(window.importData.step_method_selection),
              { trigger: true }
            );
          } else {
            MailPoet.Modal.loading(false);
            let errorNotice = MailPoet.I18n.t('noValidRecords');
            errorNotice = errorNotice.replace('[link]', MailPoet.I18n.t('csvKBLink'));
            errorNotice = errorNotice.replace('[/link]', '</a>');
            MailPoet.Notice.error(errorNotice);
          }
        },
      };
    }

    function displayMailChimpLists(data) {
      const listSelectElement = mailChimpListsContainerElement.find('select');
      if (listSelectElement.data('select2')) {
        listSelectElement.select2('data', data);
        listSelectElement.trigger('change');
      } else {
        listSelectElement
          .select2({
            data,
            width: '20em',
            templateResult(item) {
              return item.name;
            },
            templateSelection(item) {
              return item.name;
            },
          })
          .change((event) => {
            if (jQuery(event.currentTarget).val() !== null) {
              toggleNextStepButton(mailChimpProcessButtonElement, 'on');
            } else {
              toggleNextStepButton(mailChimpProcessButtonElement, 'off');
            }
          })
          .trigger('change');
      }
      mailChimpListsContainerElement.show();
    }

    /*
     *  Paste
     */
    pasteInputElement
      .attr('value', pasteInputPlaceholderElement).css('color', '#999')
      .focus((event) => {
        if (jQuery(event.currentTarget).val() === pasteInputPlaceholderElement) {
          jQuery(event.currentTarget).attr('value', '').css('color', '#222');
        }
      })
      .blur((event) => {
        if (jQuery(event.currentTarget).val() === '') {
          jQuery(event.currentTarget).attr('value', pasteInputPlaceholderElement).css('color', '#999');
        }
        toggleNextStepButton(
          pasteProcessButtonElement,
          (event.currentTarget.value.trim() !== '') ? 'on' : 'off'
        );
      })
      .keyup((event) => {
        toggleNextStepButton(
          pasteProcessButtonElement,
          (event.currentTarget.value.trim() !== '') ? 'on' : 'off'
        );
      });

    pasteProcessButtonElement.click(() => {
      const pasteSize = encodeURI(pasteInputElement.val()).split(/%..|./).length - 1;
      MailPoet.Notice.hide();
      // get an approximate size of textarea paste in bytes
      if (pasteSize > window.maxPostSizeBytes) {
        MailPoet.Notice.error(MailPoet.I18n.t('maxPostSizeNotice'));
        return;
      }
      // delay loading indicator for 10ms or else it's just too fast :)
      MailPoet.Modal.loading(true);
      setTimeout(() => {
        Papa.parse(pasteInputElement.val(), papaParserConfig(false));
      }, 10);
    });

    /*
     *  CSV file
     */
    uploadElement.change((event) => {
      const ext = event.currentTarget.value.match(/[^.]+$/);
      MailPoet.Notice.hide();
      if (ext === null || ext[0].toLowerCase() !== 'csv') {
        event.currentTarget.value.val('');
        MailPoet.Notice.error(MailPoet.I18n.t('wrongFileFormat'));
      }

      toggleNextStepButton(
        uploadProcessButtonElement,
        (event.currentTarget.value.trim() !== '') ? 'on' : 'off'
      );
    });

    uploadProcessButtonElement.click(() => {
      if (uploadElement.val().trim() !== '') {
        // delay loading indicator for 10ms or else it's just too fast :)
        MailPoet.Modal.loading(true);
        setTimeout(() => {
          uploadElement.parse({
            config: papaParserConfig(true),
          });
        }, 10);
      }
    });

    /*
     *  MailChimp
     */
    mailChimpKeyInputElement.keyup((event) => {
      if (event.currentTarget.value.trim() === ''
        || !/[a-zA-Z0-9]{32}-/.exec(event.currentTarget.value.trim())) {
        mailChimpListsContainerElement.hide();
        jQuery('.mailpoet_mailchimp-key-status')
          .html('')
          .removeClass('mailpoet_mailchimp-ok mailpoet_mailchimp-error');
        toggleNextStepButton(mailChimpProcessButtonElement, 'off');
      }
    });

    mailChimpKeyVerifyButtonElement.click(() => {
      MailPoet.Modal.loading(true);
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'importExport',
        action: 'getMailChimpLists',
        data: {
          api_key: mailChimpKeyInputElement.val(),
        },
      }).always(() => {
        MailPoet.Modal.loading(false);
      }).done((response) => {
        jQuery('.mailpoet_mailchimp-key-status')
          .html('')
          .removeClass()
          .addClass('mailpoet_mailchimp-key-status mailpoet_mailchimp-ok');
        if (response.data.length === 0) {
          jQuery('.mailpoet_mailchimp-key-status').html(MailPoet.I18n.t('noMailChimpLists'));
          mailChimpListsContainerElement.hide();
          toggleNextStepButton(mailChimpProcessButtonElement, 'off');
        } else {
          displayMailChimpLists(response.data);
        }
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
      });
    });

    mailChimpProcessButtonElement.click(() => {
      if (mailChimpProcessButtonElement.closest('table a').hasClass('button-disabled')) {
        return;
      }
      MailPoet.Modal.loading(true);
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'importExport',
        action: 'getMailChimpSubscribers',
        data: {
          api_key: mailChimpKeyInputElement.val(),
          lists: mailChimpListsContainerElement.find('select').val(),
        },
      }).always(() => {
        MailPoet.Modal.loading(false);
      }).done((response) => {
        window.importData.step_method_selection = response.data;
        MailPoet.trackEvent('Subscribers import started', {
          source: 'MailChimp',
          'MailPoet Free version': window.mailpoet_version,
        });
        router.navigate('step_data_manipulation', { trigger: true });
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
      });
    });
  });

  router.on('route:step_input_validation', () => {
    if (typeof (window.importData.step_method_selection) === 'undefined') {
      router.navigate('step_method_selection', { trigger: true });
      return;
    }
    showCurrentStep();
    const container = document.getElementById('step_input_validation');

    if (container) {
      ReactDOM.render(
        <StepInputValidation
          navigate={router.navigate}
          importData={window.importData.step_method_selection}
        />,
        container
      );
    }
  });

  router.on('route:step_data_manipulation', () => {
    let fillerPosition;
    let importResults;
    let duplicates;
    if (typeof (window.importData.step_method_selection) === 'undefined') {
      router.navigate('step_method_selection', { trigger: true });
      return;
    }
    // define reusable variables
    const nextStepButton = jQuery('#next_step');
    const previousStepButton = jQuery('#return_to_previous');
    // create a copy of subscribers object for further manipulation
    const subscribers = jQuery.extend(true, {}, window.importData.step_method_selection);
    const subscribersDataTemplate = Handlebars.compile(jQuery('#subscribers_data_template').html());
    const subscribersDataTemplatePartial = Handlebars.compile(jQuery('#subscribers_data_template_partial').html());
    const subscribersDataParseResultsTemplate = Handlebars.compile(jQuery('#subscribers_data_parse_results_template').html());
    const segmentSelectElement = jQuery('#mailpoet_segments_select');
    const maxRowsToShow = 10;
    const filler = '. . .';
    // create an array of filler data with the same number of
    // elements as in the subscribers' data row
    const fillerArray = Array(...new Array(subscribers.subscribers[0].length))
      .map(String.prototype.valueOf, filler);

    showCurrentStep();

    function toggleNextStepButton(condition) {
      const disabled = 'button-disabled';
      if (condition === 'on') {
        nextStepButton.removeClass(disabled);
        return;
      }
      nextStepButton.addClass(disabled);
    }

    // hide previous statistics/import results
    jQuery('#subscribers_data_parse_results:visible').html('');
    jQuery('#subscribers_data_import_results:visible').hide();

    // show parse statistics if any duplicate/invalid records were found
    if (subscribers.invalid.length || subscribers.duplicate.length) {
      // count repeating e-mails inside duplicate array and present them in
      // 'email (xN)' format
      duplicates = {};
      subscribers.duplicate.forEach((subscriberEmail) => {
        duplicates[subscriberEmail] = (duplicates[subscriberEmail] || 0) + 1;
      });
      subscribers.duplicate = [];
      Object.keys(duplicates).forEach((email) => {
        if (duplicates[email] > 1) {
          subscribers.duplicate.push(`${email} (x${duplicates[email]})`);
        } else {
          subscribers.duplicate.push(email);
        }
      });

      importResults = {
        notice: MailPoet.I18n.t('importNoticeSkipped').replace(
          '%1$s',
          `<strong>${subscribers.invalid.length + subscribers.duplicate.length}</strong>`
        ),
        invalid: (subscribers.invalid.length)
          ? MailPoet.I18n.t('importNoticeInvalid')
            .replace('%1$s', `<strong>${subscribers.invalid.length.toLocaleString()}</strong>`)
            .replace('%2$s', subscribers.invalid.join(', '))
          : null,
        duplicate: (subscribers.duplicate.length)
          ? MailPoet.I18n.t('importNoticeDuplicate')
            .replace('%1$s', `<strong>${subscribers.duplicate.length}</strong>`)
            .replace('%2$s', subscribers.duplicate.join(', '))
          : null,
      };
      jQuery('#subscribers_data_parse_results').html(
        subscribersDataParseResultsTemplate(importResults)
      );
    }

    jQuery('.mailpoet_subscribers_data_parse_results_details_show')
      .click((event) => {
        const details = jQuery('.mailpoet_subscribers_data_parse_results_details');
        jQuery(details).toggle();
        event.currentTarget.text((jQuery(details).is(':visible'))
          ? MailPoet.I18n.t('hideDetails')
          : MailPoet.I18n.t('showDetails'));
      });

    // show available segments
    if (window.mailpoetSegments.length) {
      jQuery('.mailpoet_segments').show();
    } else {
      jQuery('.mailpoet_no_segments').show();
    }

    function enableSegmentSelection(segments) {
      if (segmentSelectElement.data('select2')) {
        segmentSelectElement
          .html('')
          .select2('destroy');
        toggleNextStepButton('off');
      }
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
            toggleNextStepButton('off');
          } else {
            jQuery('[data-id="notice_segmentSelection"]').remove();
            if (!jQuery('.mailpoet_notice.error:visible').length) {
              toggleNextStepButton('on');
            }
          }
        });
    }

    jQuery('.mailpoet_create_segment').click(() => {
      MailPoet.Modal.popup({
        title: MailPoet.I18n.t('addNewList'),
        template: jQuery('#new_segment_template').html(),
      });
      jQuery('#new_segment_name').keypress((e) => {
        if (e.which === 13) {
          jQuery('#new_segment_process').click();
        }
      });
      jQuery('#new_segment_process').click(() => {
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
        }).done((response) => {
          let selectedValues;
          window.mailpoetSegments.push({
            id: response.data.id,
            name: response.data.name,
            subscriberCount: 0,
          });

          selectedValues = segmentSelectElement.val();
          if (selectedValues === null) {
            selectedValues = [response.data.id];
          } else {
            selectedValues.push(response.data.id);
          }

          enableSegmentSelection(window.mailpoetSegments);
          segmentSelectElement.val(selectedValues).trigger('change');
          jQuery('.mailpoet_segments:hidden').show();
          jQuery('.mailpoet_no_segments:visible').hide();
          MailPoet.Modal.close();
        }).fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.hide();
            MailPoet.Notice.error(
              response.errors.map(error => error.message),
              { positionAfter: '#new_segment_name' }
            );
          }
        });
      });
      jQuery('#new_segment_cancel').click(() => {
        MailPoet.Modal.close();
      });
    });

    // register partial template that will contain subscribers data
    Handlebars.registerPartial(
      'subscribers_data_template_partial',
      subscribersDataTemplatePartial
    );

    // autodetect column types
    Handlebars.registerHelper(
      'show_and_match_columns',
      (helperSubscribers, options) => {
        const displayedColumns = [];
        const displayedColumnsIds = [];
        let columnData;
        let columnId;
        let headerName;
        let headerNameMatch;
        // go through all elements of the first row in subscribers data
        Object
          .keys(helperSubscribers.subscribers[0])
          .forEach((i) => {
            columnData = helperSubscribers.subscribers[0][i];
            columnId = 'ignore'; // set default column type
            // if the column is not undefined and has a valid e-mail, set type as email
            if (columnData % 1 !== 0 && window.mailpoet_email_regex.test(columnData)) {
              columnId = 'email';
            } else if (helperSubscribers.header) {
              headerName = helperSubscribers.header[i];
              headerNameMatch = window.mailpoetColumns.map(el => el.name).indexOf(headerName);
              // set column type using header
              if (headerNameMatch !== -1) {
                columnId = window.mailpoetColumns[headerNameMatch].id;
              } else if (headerName) { // set column type using header name
                if (/first|first name|given name/i.test(headerName)) {
                  columnId = 'first_name';
                } else if (/last|last name/i.test(headerName)) {
                  columnId = 'last_name';
                }
              }
            }
            // make sure the column id has not been previously selected
            // (e.g., subscriber_first_name shouldn't be autodetected twice),
            // except for "ignore"
            columnId = (columnId !== 'ignore'
              && displayedColumnsIds.indexOf(columnId) === -1)
              ? columnId
              : 'ignore';
            displayedColumns[i] = { column_id: columnId };
            displayedColumnsIds.push(columnId);
          });
        return options.fn(displayedColumns);
      }
    );

    // sanitize unsafe data
    Handlebars.registerHelper('sanitize_data', data => ((data instanceof Handlebars.SafeString)
      ? data
      : new Handlebars.SafeString(Handlebars.Utils.escapeExpression(data))));

    // start array index from 1
    Handlebars.registerHelper('calculate_index', (rawIndex) => {
      const index = parseInt(rawIndex, 10);
      // display filler data (e.g., ellipsis) if we've reached the maximum number of rows and
      // subscribers count is greater than the maximum number of rows we're displaying
      if (index === maxRowsToShow && subscribers.subscribersCount > (maxRowsToShow + 1)) {
        fillerPosition = index;
        return filler;
      }
      if (index === (subscribers.subscribers.length - 1)) {
        // if we're on the last line, show the total count of subscribers data
        return subscribers.subscribersCount.toLocaleString();
      }
      return index + 1;
    });

    // reduce subscribers object if the total length is greater than the
    // maximum number of defined rows
    if (subscribers.subscribersCount > (maxRowsToShow + 1)) {
      subscribers.subscribers.splice(
        maxRowsToShow, subscribers.subscribersCount - (maxRowsToShow + 1),
        fillerArray
      );
    }

    // filter subscribers' data to detect dates, emails, etc.
    function filterSubscribers() {
      const subscribersClone = jQuery.extend(true, {}, subscribers);
      let preventNextStep = false;
      jQuery(
        '[data-id="notice_invalidEmail"], [data-id="notice_invalidDate"]'
      )
        .remove();
      const displayedColumns = jQuery.map(
        jQuery('.mailpoet_subscribers_column_data_match'), (element, elementIndex) => {
          const columnId = jQuery(element).data('column-id');
          const validationRule = jQuery(element).data('validation-rule');
          jQuery(element).val(columnId).trigger('change');
          return {
            id: columnId,
            index: elementIndex,
            validationRule,
            element,
          };
        }
      );
      // iterate through the object of mailpoet columns
      jQuery.map(window.mailpoetColumns, (column) => {
        let firstRowData;
        let validationRule;
        let testedFormat;
        let allowedDateFormats;
        // check if the column id matches the selected id of one of the
        // subscriber's data columns
        const matchedColumn = _.find(
          displayedColumns,
          data => data.id === column.id
        );
        // EMAIL filter: if the first value in the column doesn't have a valid
        // email, hide the next button
        if (column.id === 'email') {
          if (!window.mailpoet_email_regex.test(
            subscribersClone.subscribers[0][matchedColumn.index]
          )
          ) {
            preventNextStep = true;
            if (!jQuery('[data-id="notice_invalidEmail"]').length) {
              MailPoet.Notice.error(MailPoet.I18n.t('columnContainsInvalidElement'), {
                static: true,
                scroll: true,
                hideClose: true,
                id: 'invalidEmail',
              });
            }
          } else {
            MailPoet.Notice.hide('invalidEmail');
          }
        }
        // DATE filter: if column type is date, check if we can recognize it
        if (column.type === 'date' && matchedColumn) {
          allowedDateFormats = [
            Moment.ISO_8601,
            'YYYY/MM/DD',
            'MM/DD/YYYY',
            'DD/MM/YYYY',
            'YYYY/MM/DD',
            'YYYY/DD/MM',
            'MM/YYYY',
            'YYYY/MM',
            'YYYY',
          ];
          firstRowData = subscribersClone.subscribers[0][matchedColumn.index];
          validationRule = false;
          // check if date exists
          if (firstRowData.trim() === '') {
            subscribersClone.subscribers[0][matchedColumn.index] = `<span class="mailpoet_data_match mailpoet_import_error" title="${MailPoet.I18n.t('noDateFieldMatch')}">${MailPoet.I18n.t('emptyFirstRowDate')}</span> `;
            preventNextStep = true;
          } else {
            Object.keys(allowedDateFormats).forEach((format) => {
              testedFormat = allowedDateFormats[format];
              if (Moment(firstRowData, testedFormat, true).isValid()) {
                validationRule = (typeof (testedFormat) === 'function')
                  ? 'datetime'
                  : testedFormat;
                // set validation on the column element
                jQuery(matchedColumn.element).data('validation-rule', validationRule);
                return;
              }
              if (validationRule === 'datetime') {
                validationRule = Moment.ISO_8601;
              }
            });
          }
          jQuery.map(subscribersClone.subscribers, (dataSubscribers, index) => {
            const data = dataSubscribers;
            const rowData = data[matchedColumn.index];
            const date = Moment(rowData, testedFormat, true);
            if (index === fillerPosition || rowData.trim() === '') return;
            // validate date
            if (date.isValid()) {
              data[matchedColumn.index] = new Handlebars.SafeString(
                `${Handlebars.Utils.escapeExpression(data[matchedColumn.index])}<span class="mailpoet_data_match" title="${MailPoet.I18n.t('verifyDateMatch')}">${MailPoet.Date.format(date)}</span> `
              );
            } else {
              data[matchedColumn.index] = new Handlebars.SafeString(
                `${Handlebars.Utils.escapeExpression(data[matchedColumn.index])}<span class="mailpoet_data_match mailpoet_import_error" title="${MailPoet.I18n.t('noDateFieldMatch')}">${new Handlebars.SafeString(MailPoet.I18n.t('dateMatchError'))}</span> `
              );
              preventNextStep = true;
            }
          });
          if (preventNextStep && !jQuery('.mailpoet_invalidDate').length) {
            MailPoet.Notice.error(MailPoet.I18n.t('columnContainsInvalidDate'), {
              static: true,
              scroll: true,
              hideClose: true,
              id: 'invalidDate',
            });
          }
        }
      });
      // refresh table with susbcribers' data
      jQuery('#subscribers_data > table > tbody')
        .html(subscribersDataTemplatePartial(subscribersClone));

      if (preventNextStep) {
        toggleNextStepButton('off');
      } else if (!jQuery('.mailpoet_notice.error:visible').length
        && segmentSelectElement.val()) {
        toggleNextStepButton('on');
      }
    }

    // render template
    jQuery('#subscribers_data > table').html(subscribersDataTemplate(subscribers));

    // filter displayed data
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
            const data = jQuery(selectElement.$element).mailpoetSerializeObject();

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
              jQuery(selectElement).data('validation-rule', false);
              filterSubscribers();
              // close popup
              MailPoet.Modal.close();
            }).fail((response) => {
              if (response.errors.length > 0) {
                MailPoet.Notice.error(
                  response.errors.map(error => error.message),
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
        filterSubscribers();
      });

    previousStepButton.off().on('click', () => {
      router.navigate(
        getDataManipulationPreviousStepLink(window.importData.step_method_selection),
        { trigger: true }
      );
    });

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

    if (window.importData.step_data_manipulation.errors.length > 0) {
      MailPoet.Notice.error(_.flatten(window.importData.step_data_manipulation.errors));
    }

    MailPoet.trackEvent('Subscribers import finished', {
      'Subscribers created': window.importData.step_data_manipulation.created,
      'Subscribers updated': window.importData.step_data_manipulation.updated,
      'MailPoet Free version': window.mailpoet_version,
    });

    // display statistics
    const subscribersDataImportResultsTemplate = Handlebars.compile(jQuery('#subscribers_data_import_results_template').html());
    const exportMenuElement = jQuery('span.mailpoet_export');
    const importResults = {
      created: (window.importData.step_data_manipulation.created)
        ? MailPoet.I18n.t('subscribersCreated')
          .replace('%1$s', `<strong>${window.importData.step_data_manipulation.created.toLocaleString()}</strong>`)
          .replace('%2$s', `"${window.importData.step_data_manipulation.segments.join('", "')}"`)
        : false,
      updated: (window.importData.step_data_manipulation.updated)
        ? MailPoet.I18n.t('subscribersUpdated')
          .replace('%1$s', `<strong>${window.importData.step_data_manipulation.updated.toLocaleString()}</strong>`)
          .replace('%2$s', `"${window.importData.step_data_manipulation.segments.join('", "')}"`)
        : false,
      no_action: (
        !window.importData.step_data_manipulation.created
        && !window.importData.step_data_manipulation.updated
      ),
      added_to_segment_with_welcome_notification:
      window.importData.step_data_manipulation.added_to_segment_with_welcome_notification,
    };

    jQuery('#subscribers_data_import_results')
      .html(subscribersDataImportResultsTemplate(importResults))
      .show();

    jQuery('a.mailpoet_import_again').off().click(() => {
      jQuery('#subscribers_data_import_results').hide();
      router.navigate('step_method_selection', { trigger: true });
    });

    jQuery('a.mailpoet_view_subscribers').off().click(() => {
      window.location.href = 'admin.php?page=mailpoet-subscribers';
    });

    // if new subscribers were created and the export menu item is hidden
    // (it's shown only when there are subscribers), display it
    if (importResults.created && exportMenuElement.not(':visible')) {
      exportMenuElement.show();
    }

    // reset previous step's data so that coming back to this step is prevented
    window.importData.step_method_selection = undefined;
    window.importData.step_data_manipulation = undefined;
  });

  if (!Backbone.History.started) {
    Backbone.history.start();
  }
});
