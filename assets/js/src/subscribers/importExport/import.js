define(
[
      'backbone',
      'underscore',
      'jquery',
      'mailpoet',
      'handlebars',
      'papaparse',
      'asyncqueue',
      'xss',
      'moment',
      'select2'
    ],
    function (
      Backbone,
      _,
      jQuery,
      MailPoet,
      Handlebars,
      Papa,
      AsyncQueue,
      xss,
      Moment
    ) {
      if (!jQuery('#mailpoet_subscribers_import').length) {
        return;
      }
      jQuery(document).ready(function () {
        jQuery('input[name="select_method"]').attr('checked', false);
        // configure router
        router = new (Backbone.Router.extend({
          routes: {
            '': 'home',
            'step1': 'step1',
            'step2': 'step2',
            'step3': 'step3'
          },
          home: function () {
            this.navigate('step1', {trigger: true});
          }
        }));

        function showCurrentStep() {
          MailPoet.Notice.hide();
          MailPoet.Modal.loading(false);
          jQuery('#mailpoet_subscribers_import > div[id^="step"]').hide();
          jQuery(location.hash).show();
        }

        /*
         *  STEP 1 (upload or copy/paste)
         */
        router.on('route:step1', function () {
          // set or reset temporary validation rule on all columns
          mailpoetColumns = jQuery.map(mailpoetColumns, function (column, columnIndex) {
            column.validation_rule = false;
            return column;
          });

          if (typeof (importData.step1) !== 'undefined') {
            showCurrentStep();
            return;
          }

          // render process button for each method
          var methodProcessContainerTemplate =
              Handlebars.compile(jQuery('#method_process_template').html());
          jQuery('.mailpoet_method_process').html(methodProcessContainerTemplate());

          // define reusable variables
          var currentStepE = jQuery(location.hash),
              methodSelectionElement = jQuery('#select_method'),
              pasteInputElement = jQuery('#paste_input'),
              pasteInputPlaceholderElement =
                  pasteInputElement.data('placeholder').replace(/\\n/g, '\n'),
              pasteProcessButtonElement =
                  jQuery('#method_paste > div.mailpoet_method_process')
                      .find('a.mailpoet_process'),
              mailChimpKeyInputElement = jQuery('#mailchimp_key'),
              mailChimpKeyVerifyButtonElement = jQuery('#mailchimp_key_verify'),
              mailChimpListsContainerElement = jQuery('#mailchimp_lists'),
              mailChimpProcessButtonElement =
                  jQuery('#method_mailchimp > div.mailpoet_method_process')
                      .find('a.mailpoet_process'),
              uploadElement = jQuery('#file_local'),
              uploadProcessButtonElement =
                  jQuery('#method_file > div.mailpoet_method_process')
                      .find('a.mailpoet_process');

          // define method change behavior
          methodSelectionElement.change(function () {
            MailPoet.Notice.hide();
            var available_methods = jQuery(':radio[name="select_method"]'),
                selected_method =
                    available_methods.index(available_methods.filter(':checked'));
            // hide all methods
            currentStepE.find('.inside')
                .children('div[id^="method_"]')
                .hide();
            // show selected method
            currentStepE.find('.inside')
                .children('div[id^="method_"]:eq(' + selected_method + ')')
                .show()
                .find('table')
                .show();
          });

          // start step 1
          showCurrentStep();

          /*
           *  Paste
           */
          pasteInputElement
              .attr('value', pasteInputPlaceholderElement).css('color', "#999")
              .focus(function () {
                if (jQuery(this).val() === pasteInputPlaceholderElement) {
                  jQuery(this).attr('value', '').css('color', '#222');
                }
              })
              .blur(function () {
                if (jQuery(this).val() === '') {
                  jQuery(this).attr('value', pasteInputPlaceholderElement).css('color', "#999");
                }
              })
              .keyup(function () {
                toggleNextStepButton(
                    pasteProcessButtonElement,
                    (this.value.trim() !== '') ? 'on' : 'off'
                );
              });

          pasteProcessButtonElement.click(function () {
            MailPoet.Notice.hide();
            // get an approximate size of textarea paste in bytes
            var pasteSize = encodeURI(pasteInputElement.val()).split(/%..|./).length - 1;
            if (pasteSize > maxPostSizeBytes) {
              MailPoet.Notice.error(MailPoet.I18n.t('maxPostSizeNotice'));
              return;
            }
            // delay loading indicator for 10ms or else it's just too fast :)
            MailPoet.Modal.loading(true);
            setTimeout(function () {
              Papa.parse(pasteInputElement.val(), parseCSV());
            }, 10);
          });

          /*
           *  CSV file
           */
          uploadElement.change(function () {
            MailPoet.Notice.hide();
              var ext = this.value.match(/\.(.+)$/);
              if (ext === null || ext[1].toLowerCase() !== 'csv') {
                this.value = '';
                MailPoet.Notice.error(MailPoet.I18n.t('wrongFileFormat'));
              }

            toggleNextStepButton(
                uploadProcessButtonElement,
                (this.value.trim() !== '') ? 'on' : 'off'
            );
          });

          uploadProcessButtonElement.click(function () {
            if (uploadElement.val().trim() !== '') {
              // delay loading indicator for 10ms or else it's just too fast :)
              MailPoet.Modal.loading(true);
              setTimeout(function () {
                uploadElement.parse({
                  config: parseCSV()
                })
              }, 10);
            }
          });

          /*
           *  MailChimp
           */
          mailChimpKeyInputElement.keyup(function () {
            if (this.value.trim() === ''
                || !/[a-zA-Z0-9]{32}-/.exec(this.value.trim())) {
              mailChimpListsContainerElement.hide();
              jQuery('.mailpoet_mailchimp-key-status')
                  .html('')
                  .removeClass('mailpoet_mailchimp-ok mailpoet_mailchimp-error');
              toggleNextStepButton(mailChimpProcessButtonElement, 'off');
            }
          });

          mailChimpKeyVerifyButtonElement.click(function () {
            MailPoet.Modal.loading(true);
            MailPoet.Ajax.post({
              endpoint: 'importExport',
              action: 'getMailChimpLists',
              data: {
                api_key: mailChimpKeyInputElement.val()
              }
            }).always(function() {
              MailPoet.Modal.loading(false);
            }).done(function(response) {
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
            }).fail(function(response) {
              if (response.errors.length > 0) {
                MailPoet.Notice.error(
                  response.errors.map(function(error) { return error.message; }),
                  { scroll: true }
                );
              }
            });
          });

          mailChimpProcessButtonElement.click(function () {
            if (mailChimpProcessButtonElement.closest('table a').hasClass('button-disabled')) {
              return;
            }
            MailPoet.Modal.loading(true);
            MailPoet.Ajax.post({
              endpoint: 'importExport',
              action: 'getMailChimpSubscribers',
              data: {
                api_key: mailChimpKeyInputElement.val(),
                lists: mailChimpListsContainerElement.find('select').val()
              }
            }).always(function(response) {
              MailPoet.Modal.loading(false);
            }).done(function(response) {
              importData.step1 = response.data;
              router.navigate('step2', {trigger: true});
            }).fail(function(response) {
              if (response.errors.length > 0) {
                MailPoet.Notice.error(
                  response.errors.map(function(error) { return error.message; }),
                  { scroll: true }
                );
              }
            });
          });

          function displayMailChimpLists(data) {
            var listSelectElement = mailChimpListsContainerElement.find('select');
            if (listSelectElement.data('select2')) {
              listSelectElement.select2('data', data);
              listSelectElement.trigger('change');
            }
            else {
              listSelectElement
                  .select2({
                    data: data,
                    width: '20em',
                    templateResult: function (item) {
                      return item.name;
                    },
                    templateSelection: function (item) {
                      return item.name;
                    }
                  })
                  .change(function () {
                    if (jQuery(this).val() !== null) {
                      toggleNextStepButton(mailChimpProcessButtonElement, 'on');
                    }
                    else {
                      toggleNextStepButton(mailChimpProcessButtonElement, 'off');
                    }
                  })
                  .trigger('change');
            }
            mailChimpListsContainerElement.show();
          }

          function toggleNextStepButton(element, condition) {
            var disabled = 'button-disabled';
            if (condition === 'on') {
              element.closest('table a').removeClass(disabled);
              return;
            }
            element.closest('table a').addClass(disabled);
          }

          function parseCSV() {
            var processedSubscribers = [],
                parsedEmails = [],
                duplicateEmails = [],
                invalidEmails = [],
                emailColumnPosition = null,
                columnCount = null,
                isHeaderFound = false,
                advancedOptionHeader = true,
                advancedOptionDelimiter = '',
                advancedOptionNewline = '',
                advancedOptionComments = false,
                // trim spaces, commas, periods,
                // single/double quotes and convert to lowercase
                detectAndCleanupEmail = function (email) {
                  var test;
                  // decode HTML entities
                  email = jQuery('<div />').html(email).text();
                  email = email
                    .toLowerCase()
                    // left/right trim spaces, punctuation (e.g., " 'email@email.com'; ")
                    // right trim non-printable characters (e.g., "email@email.com�")
                    .replace(/^["';.,\s]+|[^\x20-\x7E]+$|["';.,_\s]+$/g, '')
                    // remove spaces (e.g., "email @ email . com")
                    // remove urlencoded characters
                    .replace(/\s+|%\d+|,+/g, '');
                  // detect e-mails that will be otherwise rejected by email regex
                  if (test = /<(.*?)>/.exec(email)) {
                    // is the email inside angle brackets (e.g., 'some@email.com <some@email.com>')?
                    email = test[1].trim();
                  }
                  if (test = /mailto:(?:\s+)?(.*)/.exec(email)) {
                    // is the email in 'mailto:email' format?
                    email = test[1].trim();
                  }
                  return email;
                };

            return {
              skipEmptyLines: true,
              delimiter: advancedOptionDelimiter,
              newline: advancedOptionNewline,
              comments: advancedOptionComments,
              error: function () {
                MailPoet.Notice.hide();
                MailPoet.Notice.error(MailPoet.I18n.t('dataProcessingError'));
              },
              complete: function (CSV) {
                for (var rowCount in CSV.data) {
                  var rowData = CSV.data[rowCount].map(function (el) {
                        return filterXSS(el.trim());
                      }),
                      rowColumnCount = rowData.length;
                  // set the number of row elements based on the first non-empty row
                  if (columnCount === null) {
                    columnCount = rowColumnCount;
                  }
                  // Process the row with the following assumptions:
                  // 1. Each row should contain the same number of elements
                  // 2. There should be at least 1 valid (as per HTML5 e-mail regex)
                  // e-mail address on each row EXCEPT when the header option is set to true
                  // 3. Duplicate addresses are skipped
                  if (rowColumnCount === columnCount) {
                    // determine position of email address inside an array; this is
                    // done once and then email regex is run just on that element for each row
                    if (emailColumnPosition === null) {
                      for (var column in rowData) {
                        var email = detectAndCleanupEmail(rowData[column]);
                        if (emailColumnPosition === null
                            && emailRegex.test(email)) {
                          emailColumnPosition = column;
                          parsedEmails[email] = true; // add current e-mail to an object index
                          rowData[column] = email;
                          processedSubscribers[email] = rowData;
                        }
                      }
                      if (emailColumnPosition === null
                          && advancedOptionHeader
                          && parseInt(rowCount) === 0) {
                        isHeaderFound = true;
                        processedSubscribers[0] = rowData;
                      }
                    }
                    else if (rowData[emailColumnPosition] !== "") {
                      var email = detectAndCleanupEmail(rowData[emailColumnPosition]);
                      if (_.has(parsedEmails, email)) {
                        duplicateEmails.push(email);
                      }
                      else if (!emailRegex.test(email)) {
                        invalidEmails.push(rowData[emailColumnPosition]);
                      }
                      // if we haven't yet processed this e-mail and it passed
                      // the regex test, then process the row
                      else {
                        parsedEmails[email] = true;
                        rowData[emailColumnPosition] = email;
                        processedSubscribers[email] = rowData;
                      }
                    }
                  }
                }
                // reindex array to avoid non-numeric indices
                processedSubscribers = _.values(processedSubscribers);
                // if the header options is set, there should be at least
                // 2 data rows, otherwise at least 1 data row
                if (processedSubscribers &&
                    (isHeaderFound && processedSubscribers.length >= 2) ||
                    (!isHeaderFound && processedSubscribers.length >= 1)
                ) {
                  // since we assume that the header line is always present, we need
                  // to detect the header by checking if it contains a valid e-mail address
                  importData.step1 = {
                    'header': (!emailRegex.test(
                            processedSubscribers[0][emailColumnPosition])
                    ) ? processedSubscribers.shift() : null,
                    'subscribers': processedSubscribers,
                    'subscribersCount': processedSubscribers.length,
                    'duplicate': duplicateEmails,
                    'invalid': invalidEmails
                  };
                  router.navigate('step2', {trigger: true});
                }
                else {
                  MailPoet.Modal.loading(false);
                  var errorNotice = MailPoet.I18n.t('noValidRecords');
                  errorNotice = errorNotice.replace('[link]', MailPoet.I18n.t('csvKBLink'));
                  errorNotice = errorNotice.replace('[/link]', '</a>');
                  MailPoet.Notice.error(errorNotice);
                }
              }
            }
          }
        });

        router.on('route:step2', function () {
          if (typeof (importData.step1) === 'undefined') {
            router.navigate('step1', {trigger: true});
            return;
          }
          // define reusable variables
          var nextStepButton = jQuery('#step2_process'),
              previousStepButton = jQuery('#return_to_step1'),
          // create a copy of subscribers object for further manipulation
              subscribers = jQuery.extend(true, {}, importData.step1),
              subscribersDataTemplate =
                  Handlebars
                      .compile(jQuery('#subscribers_data_template')
                          .html()),
              subscribersDataTemplatePartial =
                  Handlebars
                      .compile(jQuery('#subscribers_data_template_partial')
                          .html()),
              subscribersDataParseResultsTemplate =
                  Handlebars
                      .compile(jQuery('#subscribers_data_parse_results_template')
                          .html()),
              segmentSelectElement = jQuery('#mailpoet_segments_select'),
              maxRowsToShow = 10,
              filler = '. . .',
          // create an array of filler data with the same number of
          // elements as in the subscribers' data row
              fillerArray = Array.apply(
                  null,
                  new Array(subscribers.subscribers[0].length)
              ).map(String.prototype.valueOf, filler),
              fillerPosition;

          showCurrentStep();

          // hide previous statistics/import results
          jQuery('#subscribers_data_parse_results:visible').html('');
          jQuery('#subscribers_data_import_results:visible').hide();

          // show parse statistics if any duplicate/invalid records were found
          if (subscribers.invalid.length || subscribers.duplicate.length) {
            // count repeating e-mails inside duplicate array and present them in
            // 'email (xN)' format
            var duplicates = {};
            subscribers.duplicate.forEach(function (email) {
              duplicates[email] = (duplicates[email] || 0) + 1;
            });
            subscribers.duplicate = [];
            for (var email in duplicates) {
              if (duplicates[email] > 1) {
                subscribers.duplicate.push(email + ' (x' + duplicates[email] + ')');
              }
              else {
                subscribers.duplicate.push(email);
              }
            }

            var import_results = {
              notice: MailPoet.I18n.t('importNoticeSkipped').replace(
                  '%1$s',
                  '<strong>' + (subscribers.invalid.length + subscribers.duplicate.length) + '</strong>'
              ),
              invalid: (subscribers.invalid.length)
                  ? MailPoet.I18n.t('importNoticeInvalid')
                  .replace('%1$s', '<strong>' + subscribers.invalid.length.toLocaleString() + '</strong>')
                  .replace('%2$s', subscribers.invalid.join(', '))
                  : null,
              duplicate: (subscribers.duplicate.length)
                  ? MailPoet.I18n.t('importNoticeDuplicate')
                  .replace('%1$s', '<strong>' + subscribers.duplicate.length + '</strong>')
                  .replace('%2$s', subscribers.duplicate.join(', '))
                  : null
            };
            jQuery('#subscribers_data_parse_results').html(
                subscribersDataParseResultsTemplate(import_results)
            );
          }

          jQuery('.mailpoet_subscribers_data_parse_results_details_show')
              .click(function () {
                var details = jQuery('.mailpoet_subscribers_data_parse_results_details');
                jQuery(details).toggle();
                this.text =
                    (jQuery(details).is(":visible"))
                        ? MailPoet.I18n.t('hideDetails')
                        : MailPoet.I18n.t('showDetails');
              });

          // show available segments
          if (mailpoetSegments.length) {
            jQuery('.mailpoet_segments').show();
          }
          else {
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
                  templateResult: function (item) {
                    item.subscriberCount = parseInt(item.subscriberCount);
                    return item.name + ' (' + item.subscriberCount.toLocaleString() + ')';
                  },
                  templateSelection: function (item) {
                    item.subscriberCount = parseInt(item.subscriberCount);
                    return item.name + ' (' + item.subscriberCount.toLocaleString() + ')';
                  }
                })
                .change(function () {
                  var segmentSelectionNotice = jQuery('[data-id="notice_segmentSelection"]');
                  if (!this.value) {
                    if (!segmentSelectionNotice.length) {
                      MailPoet.Notice.error(MailPoet.I18n.t('segmentSelectionRequired'), {
                        static: true,
                        scroll: true,
                        id: 'segmentSelection',
                        hideClose: true
                      });
                    }
                    toggleNextStepButton('off');
                  } else {
                    jQuery('[data-id="notice_segmentSelection"]').remove();
                    if (!jQuery('.mailpoet_notice.error:visible').length) {
                      toggleNextStepButton('on');
                    }
                  }
                })
          }

          jQuery('.mailpoet_create_segment').click(function() {
            MailPoet.Modal.popup({
              title: MailPoet.I18n.t('addNewList'),
              template: jQuery('#new_segment_template').html()
            })
            jQuery('#new_segment_name').keypress(function(e) {
              if (e.which == 13) {
                jQuery('#new_segment_process').click();
              }
            });
            jQuery('#new_segment_process').click(function () {
              var segmentName = jQuery('#new_segment_name').val().trim();
              var segmentDescription = jQuery('#new_segment_description').val().trim();

              MailPoet.Ajax.post({
                endpoint: 'ImportExport',
                action: 'addSegment',
                data: {
                  name: segmentName,
                  description: segmentDescription
                }
              }).done(function(response) {
                mailpoetSegments.push({
                  'id': response.data.id,
                  'name': response.data.name
                });

                var selected_values = segmentSelectElement.val();
                if (selected_values === null) {
                  selected_values = [response.data.id]
                } else {
                  selected_values.push(response.data.id);
                }

                enableSegmentSelection(mailpoetSegments);
                segmentSelectElement.val(selected_values).trigger('change');
                jQuery('.mailpoet_segments:hidden').show();
                jQuery('.mailpoet_no_segments:visible').hide();
                MailPoet.Modal.close();
              }).fail(function(response) {
                if (response.errors.length > 0) {
                  MailPoet.Notice.hide();
                  MailPoet.Notice.error(
                    response.errors.map(function(error) { return error.message; }),
                    { positionAfter: '#new_segment_name' }
                  );
                }
              });
            });
            jQuery('#new_segment_cancel').click(function () {
              MailPoet.Modal.close();
            });
          });

          // register partial template that will contain subscribers data
          Handlebars.registerPartial(
              "subscribers_data_template_partial",
              subscribersDataTemplatePartial
          );

          // autodetect column types
          Handlebars.registerHelper(
              'show_and_match_columns',
              function (subscribers, options) {
                var displayedColumns = [],
                    displayedColumnsIds = [];
                // go through all elements of the first row in subscribers data
                for (var i in subscribers.subscribers[0]) {
                  var columnData = subscribers.subscribers[0][i],
                      columnId = 'ignore'; // set default column type
                  // if the column is not undefined and has a valid e-mail, set type as email
                  if (columnData % 1 !== 0 && emailRegex.test(columnData)) {
                    columnId = 'email';
                  } else if (subscribers.header) {
                    var headerName = subscribers.header[i],
                        header_name_match = mailpoetColumns.map(function (el) {
                          return el.id;
                        }).indexOf(headerName);
                    // set column type using header
                    if (header_name_match !== -1) {
                      columnId = headerName;
                    }// set column type using header name
                    else if (headerName) {
                      if (/first|first name|given name/i.test(headerName)) {
                        columnId = 'first_name';
                      } else if (/last|last name/i.test(headerName)) {
                        columnId = 'last_name';
                      } else if (/status/i.test(headerName)) {
                        columnId = 'status';
                      }
                      /*else if (/subscribed|subscription/i.test(headerName)) {
                       columnId = 'confirmed_at';
                       } else if (/ip/i.test(headerName)) {
                       columnId = 'confirmed_ip';
                       }*/
                    }
                  }
                  // make sure the column id has not been previously selected
                  // (e.g., subscriber_first_name shouldn't be autodetected twice),
                  // except for "ignore"
                  columnId =
                      (columnId !== 'ignore'
                      && displayedColumnsIds.indexOf(columnId) === -1)
                          ? columnId
                          : 'ignore';
                  displayedColumns[i] = {'column_id': columnId};
                  displayedColumnsIds.push(columnId);
                }
                return options.fn(displayedColumns);
              });

          // start array index from 1
          Handlebars.registerHelper('show_real_index', function (index) {
            var index = parseInt(index);
            // display filler data (e.g., ellipsis) if we've reached the maximum number of rows and
            // subscribers count is greater than the maximum number of rows we're displaying
            if (index === maxRowsToShow && subscribers.subscribersCount > (maxRowsToShow + 1)) {
              fillerPosition = index;
              return filler;
            }
            // if we're on the last line, show the total count of subscribers data
            else if (index === (subscribers.subscribers.length - 1)) {
              return subscribers.subscribersCount.toLocaleString();
            } else {
              return index + 1;
            }
          });

          // reduce subscribers object if the total length is greater than the
          // maximum number of defined rows
          if (subscribers.subscribersCount > (maxRowsToShow + 1)) {
            subscribers.subscribers.splice(
                maxRowsToShow, subscribers.subscribersCount - (maxRowsToShow + 1),
                fillerArray
            );
          }

          // render template
          jQuery('#subscribers_data > table').html(subscribersDataTemplate(subscribers));

          // filter displayed data
          jQuery('select.mailpoet_subscribers_column_data_match')
              .select2({
                data: mailpoetColumnsSelect2,
                width: '15em',
                templateResult: function (item) {
                  return item.name;
                },
                templateSelection: function (item) {
                  return item.name;
                }
              })
              .on('select2:selecting', function (selectEvent) {
                var selectElement = this,
                    selectedOptionId = selectEvent.params.args.data.id;
                // CREATE CUSTOM FIELD
                if (selectedOptionId === 'create') {
                  selectEvent.preventDefault();
                  jQuery(selectElement).select2('close');
                  MailPoet.Modal.popup({
                    title: MailPoet.I18n.t('addNewField'),
                    template: jQuery('#form_template_field_form').html()
                  });
                  jQuery('#form_field_new').parsley().on('form:submit', function(parsley) {
                    // get data
                    var data = jQuery(this.$element).serializeObject();

                    // save custom field
                    MailPoet.Ajax.post({
                      endpoint: 'customFields',
                      action: 'save',
                      data: data
                    }).done(function(response) {
                      var new_column_data = {
                        'id': response.data.id,
                        'name': response.data.name,
                        'type': response.data.type,
                        'params': response.data.params,
                        'custom': true
                      };
                      // if this is the first custom column, create an "optgroup"
                      if (mailpoetColumnsSelect2.length === 2) {
                        mailpoetColumnsSelect2.push({
                          'name': MailPoet.I18n.t('userColumns'),
                          'children': []
                        });
                      }
                      mailpoetColumnsSelect2[2].children.push(new_column_data);
                      mailpoetColumns.push(new_column_data);
                      jQuery('select.mailpoet_subscribers_column_data_match')
                        .each(function () {
                          jQuery(this)
                            .html('')
                            .select2('destroy')
                            .select2({
                              data: mailpoetColumnsSelect2,
                              width: '15em',
                              templateResult: function (item) {
                                return item.name;
                              },
                              templateSelection: function (item) {
                                return item.name;
                              }
                            })
                        });
                      jQuery(selectElement).data('column-id', new_column_data.id);
                      jQuery(selectElement).data('validation-rule', false);
                      filterSubscribers();
                      // close popup
                      MailPoet.Modal.close();
                    }).fail(function(response) {
                      if (response.errors.length > 0) {
                        MailPoet.Notice.error(
                          response.errors.map(function(error) { return error.message; }),
                          { positionAfter: '#field_name' }
                        );
                      }
                    });
                    return false;
                  });
                }
                // CHANGE COLUMN
                else {
                  // check for duplicate values in all select options
                  jQuery('select.mailpoet_subscribers_column_data_match')
                      .each(function () {
                        var element = this,
                            elementId = jQuery(element).val();
                        // if another column has the same value and it's not an 'ignore', prompt user
                        if (elementId === selectedOptionId
                            && elementId !== 'ignore') {
                          if (confirm(MailPoet.I18n.t('selectedValueAlreadyMatched') + ' ' + MailPoet.I18n.t('confirmCorrespondingColumn'))) {
                            jQuery(element).data('column-id', 'ignore');
                          }
                          else {
                            selectEvent.preventDefault();
                            jQuery(selectElement).select2('close');
                          }
                        }
                      });
                }
              })
              .on('select2:select', function (selectEvent) {
                var selectElement = this,
                    selectedOptionId = selectEvent.params.data.id;
                jQuery(selectElement).data('column-id', selectedOptionId);
                filterSubscribers();
              });

          // filter subscribers' data to detect dates, emails, etc.
          function filterSubscribers() {
            jQuery(
                '[data-id="notice_invalidEmail"], [data-id="notice_invalidDate"]')
                .remove();
            var subscribersClone = jQuery.extend(true, {}, subscribers),
                preventNextStep = false,
                displayedColumns = jQuery.map(
                    jQuery('.mailpoet_subscribers_column_data_match'), function (element, elementIndex) {
                      var columnId = jQuery(element).data('column-id');
                      var validationRule = jQuery(element).data('validation-rule');
                      jQuery(element).val(columnId).trigger('change');
                      return { id: columnId, index: elementIndex, validationRule: validationRule, element: element };
                    });
            // iterate through the object of mailpoet columns
            jQuery.map(mailpoetColumns, function (column, columnIndex) {
              // check if the column id matches the selected id of one of the
              // subscriber's data columns
              var matchedColumn = _.find(displayedColumns, function(data) { return data.id === column.id; });
              // EMAIL filter: if the first value in the column doesn't have a valid
              // email, hide the next button
              if (column.id === 'email') {
                if (!emailRegex.test(subscribersClone.subscribers[0][matchedColumn.index])) {
                  preventNextStep = true;
                  if (!jQuery('[data-id="notice_invalidEmail"]').length) {
                    MailPoet.Notice.error(MailPoet.I18n.t('columnContainsInvalidElement'), {
                      static: true,
                      scroll: true,
                      hideClose: true,
                      id: 'invalidEmail'
                    });
                  }
                }
                else {
                  MailPoet.Notice.hide('invalidEmail');
                }
              }
              // DATE filter: if column type is date, check if we can recognize it
              if (column.type === 'date' && matchedColumn) {
                var allowedDateFormats = [
                  Moment.ISO_8601,
                  'YYYY/MM/DD',
                  'MM/DD/YYYY',
                  'DD/MM/YYYY',
                  'YYYY/MM/DD',
                  'YYYY/DD/MM',
                  'MM/YYYY',
                  'YYYY/MM',
                  'YYYY'
                ];
                var firstRowData = subscribersClone.subscribers[0][matchedColumn.index];
                var validationRule = false;
                // check if date exists
                if (firstRowData.trim() === '') {
                  subscribersClone.subscribers[0][matchedColumn.index] =
                    '<span class="mailpoet_data_match mailpoet_import_error" title="'
                    + MailPoet.I18n.t('noDateFieldMatch') + '">'
                    + MailPoet.I18n.t('emptyFirstRowDate')
                    + '</span>';
                  preventNextStep = true;
                }
                else {
                  for (var format in allowedDateFormats) {
                    var testedFormat = allowedDateFormats[format]
                    if (Moment(firstRowData, testedFormat, true).isValid()) {
                      var validationRule = (typeof(testedFormat) === 'function') ?
                        'datetime' :
                        testedFormat
                      // set validation on the column element
                      jQuery(matchedColumn.element).data('validation-rule', validationRule);
                      break;
                    }
                    if (validationRule === 'datetime') validationRule = Moment.ISO_8601;
                  }
                }
                jQuery.map(subscribersClone.subscribers, function (data, index) {
                  var rowData = data[matchedColumn.index];
                  if (index === fillerPosition || rowData.trim() === '') return;
                  var date = Moment(rowData, testedFormat, true);
                  // validate date
                  if (date.isValid()) {
                    data[matchedColumn.index] +=
                        '<span class="mailpoet_data_match" title="'
                        + MailPoet.I18n.t('verifyDateMatch') + '">'
                        + MailPoet.Date.format(date)
                        + '</span>';
                  }
                  else {
                    data[matchedColumn.index] +=
                      '<span class="mailpoet_data_match mailpoet_import_error" title="'
                      + MailPoet.I18n.t('noDateFieldMatch') + '">'
                      + MailPoet.I18n.t('dateMatchError')
                      + '</span>';
                    preventNextStep = true;
                  };
                });
                if (preventNextStep && !jQuery('.mailpoet_invalidDate').length) {
                  MailPoet.Notice.error(MailPoet.I18n.t('columnContainsInvalidDate'), {
                    static: true,
                    scroll: true,
                    hideClose: true,
                    id: 'invalidDate'
                  });
                }
              }
            });
            // refresh table with susbcribers' data
            jQuery('#subscribers_data > table > tbody')
                .html(subscribersDataTemplatePartial(subscribersClone));

            if (preventNextStep) {
              toggleNextStepButton('off');
            }
            else if (!jQuery('.mailpoet_notice.error:visible').length
                && segmentSelectElement.val()) {
              toggleNextStepButton('on');
            }
          }

          function toggleNextStepButton(condition) {
            var disabled = 'button-disabled';
            if (condition === 'on') {
              nextStepButton.removeClass(disabled);
              return;
            }
            nextStepButton.addClass(disabled);
          }

          previousStepButton.off().on('click', function () {
            router.navigate('step1', { trigger: true });
          });

          nextStepButton.off().on('click', function () {
            if (jQuery(this).hasClass('button-disabled')) {
              return;
            }
            MailPoet.Modal.loading(true);

            var columns = {},
              queue = new jQuery.AsyncQueue(),
              batchNumber = 0,
              batchSize = 2000,
              timestamp = Date.now() / 1000,
              subscribers = [],
              importResults = {
                'created': 0,
                'updated': 0,
                'errors': [],
                'segments': []
              },
              splitSubscribers = function (subscribers, size) {
                return subscribers.reduce(function (res, item, index) {
                  if (index % size === 0) {
                    res.push([]);
                  }
                  res[res.length - 1].push(item);
                  return res;
                }, []);
              },
              subscribers = splitSubscribers(importData.step1.subscribers, batchSize);

            _.each(jQuery('select.mailpoet_subscribers_column_data_match'),
              function (column, columnIndex) {
                var columnId = jQuery(column).data('column-id');
                var validationRule = jQuery(column).data('validation-rule');
                if (columnId === 'ignore') {
                  return;
                }
                columns[columnId] = { index: columnIndex, validation_rule: validationRule };
              });

            _.each(subscribers, function () {
              queue.add(function(queue) {
                queue.pause();
                MailPoet.Ajax.post({
                  endpoint: 'ImportExport',
                  action: 'processImport',
                  data: JSON.stringify({
                    columns: columns,
                    subscribers: subscribers[batchNumber],
                    timestamp: timestamp,
                    segments: segmentSelectElement.val(),
                    updateSubscribers: (jQuery(':radio[name="subscriber_update_option"]:checked').val() === 'yes') ? true : false
                  })
                }).done(function(response) {
                  importResults.created = response.data.created;
                  importResults.updated = response.data.updated;
                  importResults.segments = response.data.segments;
                  importResults.added_to_segment_with_welcome_notification = response.data.added_to_segment_with_welcome_notification;
                  queue.run();
                }).fail(function(response) {
                  MailPoet.Modal.loading(false);
                  if (response.errors.length > 0) {
                    MailPoet.Notice.error(
                      response.errors.map(function(error) { return error.message; }),
                      { scroll: true }
                    );
                  }
                });
                batchNumber++;
              })
            });

            queue.run();

            queue.onComplete(function () {
              MailPoet.Modal.loading(false);
              if (importResults.errors.length > 0 && !importResults.updated && !importResults.created) {
                MailPoet.Notice.error(_.flatten(importResults.errors)
                );
              }
              else {
                mailpoetSegments = importResults.segments;
                importResults.segments = _.map(segmentSelectElement.select2('data'),
                  function (data) {
                    return data.name;
                  });
                importData.step2 = importResults;
                enableSegmentSelection(mailpoetSegments);
                router.navigate('step3', {trigger: true});
              }
            });
          });

          filterSubscribers();
          enableSegmentSelection(mailpoetSegments);
        });

        router.on('route:step3', function () {
          if (typeof (importData.step2) === 'undefined') {
            router.navigate('step2', {trigger: true});
            return;
          }

          showCurrentStep();

          if (importData.step2.errors.length > 0) {
            MailPoet.Notice.error(_.flatten(importData.step2.errors));
          }

          // display statistics
          var subscribersDataImportResultsTemplate =
                  Handlebars
                      .compile(jQuery('#subscribers_data_import_results_template')
                          .html()),
              exportMenuElement = jQuery('span.mailpoet_export'),
              importResults = {
                created: (importData.step2.created)
                    ? MailPoet.I18n.t('subscribersCreated')
                    .replace('%1$s', '<strong>' + importData.step2.created.toLocaleString() + '</strong>')
                    .replace('%2$s', '"' + importData.step2.segments.join('", "') + '"')
                    : false,
                updated: (importData.step2.updated)
                    ? MailPoet.I18n.t('subscribersUpdated')
                    .replace('%1$s', '<strong>' + importData.step2.updated.toLocaleString() + '</strong>')
                    .replace('%2$s', '"' + importData.step2.segments.join('", "') + '"')
                    : false,
                no_action: (!importData.step2.created && !importData.step2.updated),
                added_to_segment_with_welcome_notification: importData.step2.added_to_segment_with_welcome_notification
              };

          jQuery('#subscribers_data_import_results')
              .html(subscribersDataImportResultsTemplate(importResults))
              .show();

          jQuery('a.mailpoet_import_again').off().click(function () {
            jQuery('#subscribers_data_import_results').hide();
            router.navigate('step1', {trigger: true});
          });

          jQuery('a.mailpoet_view_subscribers').off().click(function () {
            window.location.href = 'admin.php?page=mailpoet-subscribers';
          });

          // if new subscribers were created and the export menu item is hidden
          // (it's shown only when there are subscribers), display it
          if (importResults.created && exportMenuElement.not(':visible')) {
            exportMenuElement.show();
          }

          // reset previous step's data so that coming back to this step is prevented
          importData.step2 = undefined;
        });

        if (!Backbone.History.started) {
          Backbone.history.start();
        }
      });
    });