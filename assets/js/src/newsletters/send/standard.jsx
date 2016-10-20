define(
  [
    'react',
    'jquery',
    'underscore',
    'mailpoet',
    'form/fields/checkbox.jsx',
    'form/fields/select.jsx',
    'form/fields/text.jsx',
  ],
  function(
    React,
    jQuery,
    _,
    MailPoet,
    Checkbox,
    Select,
    Text
  ) {

    var settings = window.mailpoet_settings || {},
        currentTime = window.mailpoet_current_time || '00:00',
        defaultDateTime = window.mailpoet_current_date + ' ' + '00:00:00';
        timeOfDayItems = window.mailpoet_schedule_time_of_day,
        dateDisplayFormat = window.mailpoet_date_display_format,
        dateStorageFormat = window.mailpoet_date_storage_format;

    var datepickerTranslations = {
      closeText: MailPoet.I18n.t('close'),
      currentText: MailPoet.I18n.t('today'),
      nextText: MailPoet.I18n.t('next'),
      prevText: MailPoet.I18n.t('previous'),
      monthNames: [
        MailPoet.I18n.t('january'),
        MailPoet.I18n.t('february'),
        MailPoet.I18n.t('march'),
        MailPoet.I18n.t('april'),
        MailPoet.I18n.t('may'),
        MailPoet.I18n.t('june'),
        MailPoet.I18n.t('july'),
        MailPoet.I18n.t('august'),
        MailPoet.I18n.t('september'),
        MailPoet.I18n.t('october'),
        MailPoet.I18n.t('november'),
        MailPoet.I18n.t('december')
      ],
      monthNamesShort: [
        MailPoet.I18n.t('januaryShort'),
        MailPoet.I18n.t('februaryShort'),
        MailPoet.I18n.t('marchShort'),
        MailPoet.I18n.t('aprilShort'),
        MailPoet.I18n.t('mayShort'),
        MailPoet.I18n.t('juneShort'),
        MailPoet.I18n.t('julyShort'),
        MailPoet.I18n.t('augustShort'),
        MailPoet.I18n.t('septemberShort'),
        MailPoet.I18n.t('octoberShort'),
        MailPoet.I18n.t('novemberShort'),
        MailPoet.I18n.t('decemberShort')
      ],
      dayNames: [
        MailPoet.I18n.t('sunday'),
        MailPoet.I18n.t('monday'),
        MailPoet.I18n.t('tuesday'),
        MailPoet.I18n.t('wednesday'),
        MailPoet.I18n.t('thursday'),
        MailPoet.I18n.t('friday'),
        MailPoet.I18n.t('saturday')
      ],
      dayNamesShort: [
        MailPoet.I18n.t('sundayShort'),
        MailPoet.I18n.t('mondayShort'),
        MailPoet.I18n.t('tuesdayShort'),
        MailPoet.I18n.t('wednesdayShort'),
        MailPoet.I18n.t('thursdayShort'),
        MailPoet.I18n.t('fridayShort'),
        MailPoet.I18n.t('saturdayShort')
      ],
      dayNamesMin: [
        MailPoet.I18n.t('sundayMin'),
        MailPoet.I18n.t('mondayMin'),
        MailPoet.I18n.t('tuesdayMin'),
        MailPoet.I18n.t('wednesdayMin'),
        MailPoet.I18n.t('thursdayMin'),
        MailPoet.I18n.t('fridayMin'),
        MailPoet.I18n.t('saturdayMin')
      ],
    };

    var DateText = React.createClass({
      onChange: function(event) {
        // Swap display format to storage format
        var displayDate = event.target.value,
            storageDate = this.getStorageDate(displayDate);

        event.target.value = storageDate;
        this.props.onChange(event);
      },
      componentDidMount: function() {
        var $element = jQuery(this.refs.dateInput),
            that = this;
        if ($element.datepicker) {
          // Override jQuery UI datepicker Date parsing and formatting
          jQuery.datepicker.parseDate = function(format, value) {
            // Transform string format to Date object
            return MailPoet.Date.toDate(value, {
              parseFormat: dateDisplayFormat,
              format: format
            });
          };
          jQuery.datepicker.formatDate = function(format, value) {
            // Transform Date object to string format
            var newValue = MailPoet.Date.format(value, {
              format: format
            });
            return newValue;
          };

          $element.datepicker(_.extend({
            dateFormat: this.props.displayFormat,
            isRTL: false,
            onSelect: function(value) {
              that.onChange({
                target: {
                  name: that.getFieldName(),
                  value: value,
                },
              });
            },
          }, datepickerTranslations));

          this.datepickerInitialized = true;
        }
      },
      componentWillUnmount: function() {
        if (this.datepickerInitialized) {
          jQuery(this.refs.dateInput).datepicker('destroy');
        }
      },
      getFieldName: function() {
        return this.props.name || 'date';
      },
      getDisplayDate: function(date) {
        return MailPoet.Date.format(date, {
          parseFormat: this.props.storageFormat,
          format: this.props.displayFormat
        });
      },
      getStorageDate: function(date) {
        return MailPoet.Date.format(date, {
          parseFormat: this.props.displayFormat,
          format: this.props.storageFormat
        });
      },
      render: function() {
        return (
          <input
            type="text"
            size="10"
            name={this.getFieldName()}
            value={this.getDisplayDate(this.props.value)}
            readOnly={ true }
            onChange={this.onChange}
            ref="dateInput"
            {...this.props.validation} />
        );
      },
    });

    var TimeSelect = React.createClass({
      render: function() {
        const options = Object.keys(timeOfDayItems).map(
          (value, index) => {
            return (
              <option
                key={ 'option-' + index }
                value={ value }>
                { timeOfDayItems[value] }
              </option>
            );
          }
        );

        return (
          <select
            name={this.props.name || 'time'}
            value={this.props.value}
            onChange={this.props.onChange}
            {...this.props.validation}
          >
            {options}
          </select>
        );
      }
    });

    var DateTime = React.createClass({
      _DATE_TIME_SEPARATOR: " ",
      getInitialState: function() {
        return this._buildStateFromProps(this.props);
      },
      componentWillReceiveProps: function(nextProps) {
        this.setState(this._buildStateFromProps(nextProps));
      },
      _buildStateFromProps: function(props) {
        let value = props.value || defaultDateTime;
        const [date, time] = value.split(this._DATE_TIME_SEPARATOR)
        return {
          date: date,
          time: time,
        };
      },
      handleChange: function(event) {
        var newState = {};
        newState[event.target.name] = event.target.value;

        this.setState(newState, function() {
          this.propagateChange();
        });
      },
      propagateChange: function() {
        if (this.props.onChange) {
          return this.props.onChange({
            target: {
              name: this.props.name || '',
              value: this.getDateTime(),
            }
          })
        }
      },
      getDateTime: function() {
        return [this.state.date, this.state.time].join(this._DATE_TIME_SEPARATOR);
      },
      render: function() {
        return (
          <span>
            <DateText
              name="date"
              value={this.state.date}
              onChange={this.handleChange}
              displayFormat={dateDisplayFormat}
              storageFormat={dateStorageFormat}
              validation={this.props.dateValidation}/>
            <TimeSelect
              name="time"
              value={this.state.time}
              onChange={this.handleChange}
              validation={this.props.timeValidation} />
          </span>
        );
      }
    });

    var StandardScheduling = React.createClass({
      _getCurrentValue: function() {
        return _.defaults(
          this.props.item[this.props.field.name] || {},
          {
            isScheduled: '0',
            scheduledAt: defaultDateTime
          }
        );
      },
      handleValueChange: function(event) {
        var oldValue = this._getCurrentValue(),
            newValue = {};
        newValue[event.target.name] = event.target.value;

        return this.props.onValueChange({
          target: {
            name: this.props.field.name,
            value: _.extend({}, oldValue, newValue)
          }
        });
      },
      handleCheckboxChange: function(event) {
        event.target.value = this.refs.isScheduled.checked ? '1' : '0';
        return this.handleValueChange(event);
      },
      isScheduled: function() {
        return this._getCurrentValue().isScheduled === '1';
      },
      getDateValidation: function() {
        return {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('noScheduledDateError'),
          'data-parsley-errors-container': '#mailpoet_scheduling',
        };
      },
      render: function() {
        var schedulingOptions;

        if (this.isScheduled()) {
          schedulingOptions = (
            <span id="mailpoet_scheduling">
              <DateTime
                name="scheduledAt"
                value={this._getCurrentValue().scheduledAt}
                onChange={this.handleValueChange}
                dateValidation={this.getDateValidation()} />
              &nbsp;
              <span>
                {MailPoet.I18n.t('websiteTimeIs')} <code>{currentTime}</code>
              </span>
            </span>
          );
        }

        return (
          <div>
            <input
              ref="isScheduled"
              type="checkbox"
              value="1"
              checked={this.isScheduled()}
              name="isScheduled"
              onChange={this.handleCheckboxChange} />

            {schedulingOptions}
          </div>
        );
      },
    });

    var fields = [
      {
        name: 'subject',
        label: MailPoet.I18n.t('subjectLine'),
        tip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError')
        }
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('segments'),
        tip: MailPoet.I18n.t('segmentsTip'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
        id: "mailpoet_segments",
        endpoint: "segments",
        multiple: true,
        filter: function(segment) {
          return !!(!segment.deleted_at);
        },
        getLabel: function(segment) {
          return segment.name + ' (' + parseInt(segment.subscribers).toLocaleString() + ')';
        },
        transformChangedValue: function(segment_ids) {
          var all_segments = this.state.items;
          return _.map(segment_ids, function(id) {
            return _.find(all_segments, function(segment) {
              return segment.id === id;
            });
          });
        },
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('noSegmentsSelectedError')
        }
      },
      {
        name: 'sender',
        label: MailPoet.I18n.t('sender'),
        tip: MailPoet.I18n.t('senderTip'),
        fields: [
          {
            name: 'sender_name',
            type: 'text',
            placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
            validation: {
              'data-parsley-required': true
            }
          },
          {
            name: 'sender_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
            validation: {
              'data-parsley-required': true,
              'data-parsley-type': 'email'
            }
          }
        ]
      },
      {
        name: 'reply-to',
        label: MailPoet.I18n.t('replyTo'),
        tip: MailPoet.I18n.t('replyToTip'),
        inline: true,
        fields: [
          {
            name: 'reply_to_name',
            type: 'text',
            placeholder: MailPoet.I18n.t('replyToNamePlaceholder')
          },
          {
            name: 'reply_to_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('replyToAddressPlaceholder')
          }
        ]
      },
      {
        name: 'options',
        label: MailPoet.I18n.t('scheduleIt'),
        type: 'reactComponent',
        component: StandardScheduling,
      }
    ];

    return {
      getFields: function(newsletter) {
        return fields;
      },
      getSendButtonOptions: function(newsletter) {
        newsletter = newsletter || {};

        let isScheduled = (
          typeof newsletter.options === 'object'
          && newsletter.options.isScheduled === '1'
        );
        let options = {
          value: (isScheduled
            ? MailPoet.I18n.t('schedule')
            : MailPoet.I18n.t('send'))
        };

        if (newsletter.status === 'sent'
            || newsletter.status === 'sending') {
          options['disabled'] = 'disabled';
        }

        return options;
      },
    };
  }
);
