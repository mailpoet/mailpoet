define(
    [
      'backbone',
      'underscore',
      'jquery',
      'mailpoet',
      'handlebars',
      'papaparse',
      'select2'
    ],
    function (Backbone,
              _,
              jQuery,
              MailPoet,
              Handlebars,
              Papa) {
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
              mailChimpKeyVerifyButtonEelement = jQuery('#mailchimp_key_verify'),
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
              MailPoet.Notice.error(MailPoetI18n.maxPostSizeNotice, {
                timeout: 3000,
              });
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
              var ext = this.value.match(/\.(.+)$/)[1];
              if (ext.toLowerCase() !== 'csv') {
                this.value = '';
                MailPoet.Notice.error(MailPoetI18n.wrongFileFormat, {
                  timeout: 3000,
                });
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
              mailChimpKeyVerifyButtonEelement.prop('disabled', true);
              toggleNextStepButton(mailChimpProcessButtonElement, 'off');
            }
            else {
              mailChimpKeyVerifyButtonEelement.prop('disabled', false);
            }
          });

          mailChimpKeyVerifyButtonEelement.click(function () {
            MailPoet.Modal.loading(true);
            MailPoet.Ajax.post({
              endpoint: 'ImportExport',
              action: 'getMailChimpLists',
              data: {api_key: mailChimpKeyInputElement.val()}
            }).done(function (response) {
              if (response.result === false) {
                MailPoet.Notice.hide();
                MailPoet.Notice.error(response.error, {
                  timeout: 3000,
                });
                jQuery('.mailpoet_mailchimp-key-status')
                    .removeClass()
                    .addClass('mailpoet_mailchimp-key-status mailpoet_mailchimp-error');
                mailChimpListsContainerElement.hide();
                toggleNextStepButton(mailChimpProcessButtonElement, 'off');
              } else {
                jQuery('.mailpoet_mailchimp-key-status')
                    .html('')
                    .removeClass()
                    .addClass('mailpoet_mailchimp-key-status mailpoet_mailchimp-ok');
                if (!response.data) {
                  jQuery('.mailpoet_mailchimp-key-status').html(MailPoetI18n.noMailChimpLists);
                  mailChimpListsContainerElement.hide();
                  toggleNextStepButton(mailChimpProcessButtonElement, 'off');
                } else {
                  displayMailChimpLists(response.data);
                }
              }
              MailPoet.Modal.loading(false);
            }).error(function (error) {
              MailPoet.Modal.loading(false);
              MailPoet.Notice.error(
                  MailPoetI18n.serverError + error.statusText.toLowerCase() + '.', {
                    timeout: 3000,
                  }
              );
            });
            MailPoet.Modal.loading(false);
          });

          mailChimpProcessButtonElement.click(function () {
            if (mailChimpProcessButtonElement.closest('table a').hasClass('disabled')) {
              return;
            }
            MailPoet.Modal.loading(true);
            MailPoet.Ajax.post({
              endpoint: 'ImportExport',
              action: 'getMailChimpSubscribers',
              data: {
                api_key: mailChimpKeyInputElement.val(),
                lists: mailChimpListsContainerElement.find('select').val()
              }
            }).done(function (response) {
              if (response.result === true) {
                importData.step1 = response.data;
                router.navigate('step2', {trigger: true});
              }
              else {
                MailPoet.Notice.hide();
                MailPoet.Notice(response.message, {
                  timeout: 3000,
                });
              }
              MailPoet.Modal.loading(false);
            }).error(function () {
              MailPoet.Modal.loading(false);
              MailPoet.Notice.error(
                  MailPoetI18n.serverError + result.statusText.toLowerCase() + '.', {
                    timeout: 3000,
                  }
              );
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
                  email = email.toLowerCase();
                  var test,
                      cleanEmail =
                          email
                          // left/right trim spaces, punctuation (e.g., " 'email@email.com'; ")
                          // right trim non-printable characters (e.g., "email@email.com�")
                              .replace(/^["';.,\s]+|[^\x20-\x7E]+$|["';.,_\s]+$/g, '')
                              // remove spaces (e.g., "email @ email . com")
                              // remove urlencoded characters
                              .replace(/\s+|%\d+|,+/g, '')
                              .toLowerCase();
                  // detect e-mails that will otherwise be rejected by ^email_regex$
                  if (test = /<(.*?)>/.exec(email)) {
                    // is email inside angle brackets (e.g., 'some@email.com <some@email.com>')?
                    return test[1].trim();
                  }
                  else if (test = /mailto:(?:\s+)?(.*)/.exec(email)) {
                    // is email in 'mailto:email' format?
                    return test[1].trim();
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
                MailPoet.Notice.error(MailPoetI18n.dataProcessingError, {
                  timeout: 3000,
                });
              },
              complete: function (CSV) {
                for (var rowCount in CSV.data) {
                  var rowData = CSV.data[rowCount].map(function (el) {
                        return el.trim();
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
                  var errorNotice = MailPoetI18n.noValidRecords;
                  errorNotice = errorNotice.replace('[link]', '<a target="_blank" href="http://support.mailpoet.com/knowledgebase/importing-subscribers-with-a-csv-file?utm_source=wpadmin&utm_campaign=import">');
                  errorNotice = errorNotice.replace('[/link]', '</a>');
                  MailPoet.Notice.error(errorNotice, {
                    timeout: 3000,
                  });
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
              fillterPosition;

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
              notice: MailPoetI18n.importNoticeSkipped.replace(
                  '%1$s',
                  '<strong>' + (subscribers.invalid.length + subscribers.duplicate.length) + '</strong>'
              ),
              invalid: (subscribers.invalid.length)
                  ? MailPoetI18n.importNoticeInvalid
                  .replace('%1$s', '<strong>' + subscribers.invalid.length + '</strong>')
                  .replace('%2$s', subscribers.invalid.join(', '))
                  : null,
              duplicate: (subscribers.duplicate.length)
                  ? MailPoetI18n.importNoticeDuplicate
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
                        ? MailPoetI18n.hideDetails
                        : MailPoetI18n.showDetails;
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
            }
            segmentSelectElement
                .select2({
                  data: segments,
                  width: '20em',
                  templateResult: function (item) {
                    return (item.subscriberCount > 0)
                        ? item.name + ' (' + item.subscriberCount + ')'
                        : item.name;
                  },
                  templateSelection: function (item) {
                    return (item.subscriberCount > 0)
                        ? item.name + ' (' + item.subscriberCount + ')'
                        : item.name;
                  }
                })
                .change(function () {
                  var segmentSelectionNotice = jQuery('[data-id="notice_segmentSelection"]');
                  if (!this.value) {
                    if (!segmentSelectionNotice.length) {
                      MailPoet.Notice.error(MailPoetI18n.segmentSelectionRequired, {
                        static: true,
                        timeout: 3000,
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

          jQuery('.mailpoet_create_segment').click(function () {
            MailPoet.Modal.popup({
              title: MailPoetI18n.addNewList,
              template: jQuery('#new_segment_template').html()
            })
            jQuery('#new_segment_name').keypress(function (e) {
              if (e.which == 13) {
                jQuery('#new_segment_process').click();
              }
            });
            jQuery('#new_segment_process').click(function () {
              var segmentName = jQuery('#new_segment_name').val().trim(),
                  segmentDescription = jQuery('#new_segment_description').val().trim(),
                  isDuplicateListName = ( jQuery.map(mailpoetSegments, function (el) {
                    if (el.name.toLowerCase() === segmentName.toLowerCase()) {
                      return true;
                    }
                  }).length && segmentName) ? true : false;
              if (segmentName === '') {
                jQuery('.mailpoet_validation_error[data-error="segment_name_required"]:hidden').show();
              } else {
                jQuery('.mailpoet_validation_error[data-error="segment_name_required"]:visible').hide();
              }
              if (isDuplicateListName) {
                jQuery('.mailpoet_validation_error[data-error="segment_name_not_unique"]:hidden').show();
              } else {
                jQuery('.mailpoet_validation_error[data-error="segment_name_not_unique"]:visible').hide();
              }
              if (segmentName && !isDuplicateListName) {
                jQuery('.mailpoet_validation_error[data-error="segment_name_required"]:visible').hide();
                MailPoet.Ajax
                    .post({
                      endpoint: 'ImportExport',
                      action: 'addSegment',
                      data: {
                        name: segmentName,
                        description: segmentDescription
                      }
                    })
                    .done(function (response) {
                      if (response.result === true) {
                        mailpoetSegments.push({
                          'id': response.segment.id,
                          'name': response.segment.name
                        });

                        var selected_values = segmentSelectElement.val();
                        if (selected_values === null) {
                          selected_values = [response.segment.id]
                        } else {
                          selected_values.push(response.segment.id);
                        }

                        enableSegmentSelection(mailpoetSegments);
                        segmentSelectElement.val(selected_values).trigger('change');
                        jQuery('.mailpoet_segments:hidden').show();
                        jQuery('.mailpoet_no_segments:visible').hide();
                        MailPoet.Modal.close();
                      }
                      else {
                        MailPoet.Modal.close();
                        MailPoet.Notice.error(
                            MailPoetI18n.segmentCreateError + response.message + '.', {
                              timeout: 3000,
                            }
                        );
                      }
                    })
                    .error(function (error) {
                      MailPoet.Modal.close();
                      MailPoet.Notice.error(
                          MailPoetI18n.serverError + error.statusText.toLowerCase() + '.', {
                            timeout: 3000
                          }
                      );
                    });
              }
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
              fillterPosition = index;
              return filler;
            }
            // if we're on the last line, show the total count of subscribers data
            else if (index === (subscribers.subscribers.length - 1)) {
              return subscribers.subscribersCount;
            } else {
              return index + 1;
            }
          });

          // reduce subscribers object if the total length is geater than the
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
                if (selectedOptionId === 'create') {
                  selectEvent.preventDefault();
                  jQuery(selectElement).select2('close');
                  MailPoet.Modal.popup({
                    title: MailPoetI18n.addNewColumn,
                    template: jQuery('#new_column_template').html()
                  });
                  jQuery('#new_column_name').keypress(function (e) {
                    if (e.which == 13) {
                      jQuery('#new_column_process').click();
                    }
                  });
                  jQuery('#new_column_process').click(function () {
                    var name = jQuery('#new_column_name').val().trim(),
                        type = jQuery('#new_column_type').val().trim(),
                        columnNames = mailpoetColumns.map(function (el) {
                          return el.name.toLowerCase();
                        });
                    isDuplicateColumnName =
                        (name && columnNames.indexOf(name.toLowerCase()) > -1)
                            ? true
                            : false;
                    if (name === '') {
                      jQuery('.mailpoet_validation_error[data-error="name_required"]')
                          .show();
                    } else {
                      jQuery('.mailpoet_validation_error[data-error="name_required"]')
                          .hide();
                    }
                    if (type === '') {
                      jQuery('.mailpoet_validation_error[data-error="type_required"]')
                          .show();
                    } else {
                      jQuery('.mailpoet_validation_error[data-error="type_required"]')
                          .hide();
                    }
                    if (isDuplicateColumnName) {
                      jQuery('.mailpoet_validation_error[data-error="name_not_unique"]')
                          .show();
                    } else {
                      jQuery('.mailpoet_validation_error[data-error="name_not_unique"]')
                          .hide();
                    }
                    // create new field
                    if (name && type && !isDuplicateColumnName) {
                      MailPoet.Modal
                          .close()
                          .loading(true);
                      MailPoet.Ajax
                          .post({
                            endpoint: 'ImportExport',
                            action: 'addCustomField',
                            data: {
                              name: name,
                              type: type
                            }
                          })
                          .done(function (response) {
                            if (response.result === true) {
                              var new_column_data = {
                                'id': response.customField.id,
                                'name': name,
                                'type': type,
                                'custom': true,
                              };
                              // if this is the first custom column, create an "optgroup"
                              if (mailpoetColumnsSelect2.length === 2) {
                                mailpoetColumnsSelect2.push({
                                  'name': MailPoetI18n.userColumns,
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
                              filterSubscribers();
                            }
                            else {
                              MailPoet.Notice.error(MailPoetI18n.customFieldCreateError, {
                                timeout: 3000,
                              });
                            }
                            MailPoet.Modal.loading(false);
                          })
                          .error(function (error) {
                            MailPoet.Modal.loading(false);
                            MailPoet.Notice.error(
                                MailPoetI18n.serverError + error.statusText.toLowerCase() + '.', {
                                  timeout: 3000,
                                }
                            );
                          });
                    }
                  });
                  jQuery('#new_column_cancel').click(function () {
                    MailPoet.Modal.close();
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
                          if (confirm(MailPoetI18n.selectedValueAlreadyMatched + ' ' + MailPoetI18n.confirmCorrespondingColumn)) {
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
                displayedColumnsIds = jQuery.map(
                    jQuery('.mailpoet_subscribers_column_data_match'), function (data) {
                      var columnId = jQuery(data).data('column-id');
                      jQuery(data).val(columnId).trigger('change');
                      return columnId;
                    });
            // iterate through the object of mailpoet columns
            jQuery.map(mailpoetColumns, function (column) {
              // check if the column id matches the selected id of one of the
              // subscriber's data columns
              var matchedColumn = jQuery.inArray(column.id, displayedColumnsIds);

              // EMAIL filter: if the last value in the column doesn't have a valid
              // email, hide the next button
              if (column.id === "email") {
                if (!emailRegex.test(subscribersClone.subscribers[0][matchedColumn])) {
                  preventNextStep = true;
                  if (!jQuery('[data-id="notice_invalidEmail"]').length) {
                    MailPoet.Notice.error(MailPoetI18n.columnContainsInvalidElement, {
                      static: true,
                      timeout: 3000,
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
              if (column.type === 'date' && matchedColumn !== -1) {
                jQuery.map(subscribersClone.subscribers, function (data, position) {
                  var rowData = data[matchedColumn],
                      date = new Date(rowData.replace(/-/g, '/')), // IE doesn't like
                  // dashes as date separators
                      month_name = [
                        MailPoetI18n.january,
                        MailPoetI18n.february,
                        MailPoetI18n.march,
                        MailPoetI18n.april,
                        MailPoetI18n.may,
                        MailPoetI18n.june,
                        MailPoetI18n.july,
                        MailPoetI18n.august,
                        MailPoetI18n.september,
                        MailPoetI18n.october,
                        MailPoetI18n.november,
                        MailPoetI18n.december
                      ];

                  if (position !== fillterPosition) {
                    // check for valid date:
                    // * invalid date object returns NaN for getTime() and NaN
                    // is the only object not strictly equal to itself
                    // * date must have period/dash/slash OR be at least 4
                    // characters long (e.g., year)
                    // * must be before now
                    if (rowData.trim() === '') {
                      data[matchedColumn] =
                          '<span class="mailpoet_data_match mailpoet_import_error" title="'
                          + MailPoetI18n.noDateFieldMatch + '">'
                          + MailPoetI18n.emptyDate
                          + '</span>';
                      preventNextStep = true;
                      return;
                    }
                    else if (date.getTime() === date.getTime() &&
                        (/[.-\/]/.test(rowData) || rowData.length >= 4) &&
                        date.getTime() < (new Date()).getTime()
                    ) {
                      date = '/ '
                          + month_name[date.getMonth()]
                          + ' ' + date.getDate() + ', '
                          + date.getFullYear() + ' '
                          + date.getHours() + ':'
                          + ((date.getMinutes() < 10 ? '0' : '')
                          + date.getMinutes()) + ' '
                          + ((date.getHours() >= 12)
                                  ? MailPoetI18n.pm
                                  : MailPoetI18n.am
                          );
                      data[matchedColumn] +=
                          '<span class="mailpoet_data_match" title="'
                          + MailPoetI18n.verifyDateMatch + '">'
                          + date + '</span>';
                    }
                    else {
                      data[matchedColumn] +=
                          '<span class="mailpoet_data_match mailpoet_import_error" title="'
                          + MailPoetI18n.noDateFieldMatch + '">'
                          + MailPoetI18n.dateMatchError + '</span>';
                      preventNextStep = true;
                    }
                  }
                });
                if (preventNextStep && !jQuery('.mailpoet_invalidDate').length) {
                  MailPoet.Notice.error(MailPoetI18n.columnContainsInvalidDate, {
                    static: true,
                    timeout: 3000,
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

          previousStepButton.off().click(function () {
            router.navigate('step1', {trigger: true});
          });

          nextStepButton.off().click(function () {
            if (jQuery(this).hasClass('button-disabled')) {
              return;
            }
            MailPoet.Modal.loading(true);

            var subscribers = {};

            _.each(jQuery('select.mailpoet_subscribers_column_data_match'),
                function (column, index) {
                  var columnId = jQuery(column).data('column-id');
                  if (columnId === 'ignore') {
                    return;
                  }
                  subscribers[columnId] = [];
                  _.each(importData.step1.subscribers, function (subsciber) {
                    subscribers[columnId].push(
                        _.chain(subsciber)
                            .pick(index)
                            .toArray()
                            .flatten()
                            .value()
                    );
                  });
                  subscribers[columnId] = _.flatten(subscribers[columnId]);
                });

            MailPoet.Ajax.post({
              endpoint: 'ImportExport',
              action: 'processImport',
              data: JSON.stringify({
                subscribers: subscribers,
                segments: segmentSelectElement.val(),
                updateSubscribers: (jQuery(':radio[name="subscriber_update_option"]:checked').val() === 'yes') ? true : false
              })
            }).done(function (response) {
              MailPoet.Modal.loading(false);
              if (response.result === false) {
                MailPoet.Notice.error(response.error, {
                  timeout: 3000,
                });
              } else {
                mailpoetSegments = response.data.segments;
                response.data.segments = _.map(segmentSelectElement.select2('data'),
                    function (data) {
                      return data.name;
                    });
                importData.step2 = response.data;
                enableSegmentSelection(mailpoetSegments);
                router.navigate('step3', {trigger: true});
              }
            }).error(function (error) {
              MailPoet.Modal.loading(false);
              MailPoet.Notice.error(
                  MailPoetI18n.serverError + error.statusText.toLowerCase() + '.',  {
                    timeout: 3000,
                  }
              );
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

          // display statistics
          var subscribersDataImportResultsTemplate =
                  Handlebars
                      .compile(jQuery('#subscribers_data_import_results_template')
                          .html()),
              exportMenuElement = jQuery('span.mailpoet_export'),
              importResults = {
                created: (importData.step2.created)
                    ? MailPoetI18n.subscribersCreated
                    .replace('%1$s', '<strong>' + importData.step2.created + '</strong>')
                    .replace('%2$s', '"' + importData.step2.segments.join('", "') + '"')
                    : false,
                updated: (importData.step2.updated)
                    ? MailPoetI18n.subscribersUpdated
                    .replace('%1$s', '<strong>' + importData.step2.updated + '</strong>')
                    .replace('%2$s', '"' + importData.step2.segments.join('", "') + '"')
                    : false,
                noaction: (!importData.step2.updated && !importData.step2.created)
                    ? true
                    : false
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