define(
  [
    'react',
    'jquery',
    'underscore',
    'mailpoet',
    'wp-js-hooks',
  ],
  (
    React,
    jq,
    _,
    MailPoet,
    Hooks
  ) => {
    const jQuery = jq;

    const currentTime = window.mailpoet_current_time || '00:00';
    const defaultDateTime = `${window.mailpoet_current_date} 00:00:00`;
    const timeOfDayItems = window.mailpoet_schedule_time_of_day;
    const dateDisplayFormat = window.mailpoet_date_display_format;
    const dateStorageFormat = window.mailpoet_date_storage_format;

    const datepickerTranslations = {
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
        MailPoet.I18n.t('december'),
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
        MailPoet.I18n.t('decemberShort'),
      ],
      dayNames: [
        MailPoet.I18n.t('sunday'),
        MailPoet.I18n.t('monday'),
        MailPoet.I18n.t('tuesday'),
        MailPoet.I18n.t('wednesday'),
        MailPoet.I18n.t('thursday'),
        MailPoet.I18n.t('friday'),
        MailPoet.I18n.t('saturday'),
      ],
      dayNamesShort: [
        MailPoet.I18n.t('sundayShort'),
        MailPoet.I18n.t('mondayShort'),
        MailPoet.I18n.t('tuesdayShort'),
        MailPoet.I18n.t('wednesdayShort'),
        MailPoet.I18n.t('thursdayShort'),
        MailPoet.I18n.t('fridayShort'),
        MailPoet.I18n.t('saturdayShort'),
      ],
      dayNamesMin: [
        MailPoet.I18n.t('sundayMin'),
        MailPoet.I18n.t('mondayMin'),
        MailPoet.I18n.t('tuesdayMin'),
        MailPoet.I18n.t('wednesdayMin'),
        MailPoet.I18n.t('thursdayMin'),
        MailPoet.I18n.t('fridayMin'),
        MailPoet.I18n.t('saturdayMin'),
      ],
    };

    const DateText = React.createClass({
      onChange: function onChange(event) {
        const changeEvent = event;
        // Swap display format to storage format
        const displayDate = changeEvent.target.value;
        const storageDate = this.getStorageDate(displayDate);

        changeEvent.target.value = storageDate;
        this.props.onChange(changeEvent);
      },
      componentDidMount: function componentDidMount() {
        const $element = jQuery(this.dateInput);
        const that = this;
        if ($element.datepicker) {
          // Override jQuery UI datepicker Date parsing and formatting
          jQuery.datepicker.parseDate = function parseDate(format, value) {
            // Transform string format to Date object
            return MailPoet.Date.toDate(value, {
              parseFormat: dateDisplayFormat,
              format: format,
            });
          };
          jQuery.datepicker.formatDate = function formatDate(format, value) {
            // Transform Date object to string format
            const newValue = MailPoet.Date.format(value, {
              format: format,
            });
            return newValue;
          };

          $element.datepicker(_.extend({
            dateFormat: this.props.displayFormat,
            isRTL: false,
            onSelect: function onSelect(value) {
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
      componentWillUnmount: function componentWillUnmount() {
        if (this.datepickerInitialized) {
          jQuery(this.dateInput).datepicker('destroy');
        }
      },
      getFieldName: function getFieldName() {
        return this.props.name || 'date';
      },
      getDisplayDate: function getDisplayDate(date) {
        return MailPoet.Date.format(date, {
          parseFormat: this.props.storageFormat,
          format: this.props.displayFormat,
        });
      },
      getStorageDate: function getStorageDate(date) {
        return MailPoet.Date.format(date, {
          parseFormat: this.props.displayFormat,
          format: this.props.storageFormat,
        });
      },
      render: function render() {
        return (
          <input
            type="text"
            size="10"
            name={this.getFieldName()}
            value={this.getDisplayDate(this.props.value)}
            readOnly={true}
            disabled={this.props.disabled}
            onChange={this.onChange}
            ref={(c) => { this.dateInput = c; }}
            {...this.props.validation}
          />
        );
      },
    });

    const TimeSelect = React.createClass({
      render: function render() {
        const options = Object.keys(timeOfDayItems).map(
          (value, index) => (
            <option
              key={`option-${index}`}
              value={value}
            >
              { timeOfDayItems[value] }
            </option>
            )
        );

        return (
          <select
            name={this.props.name || 'time'}
            value={this.props.value}
            disabled={this.props.disabled}
            onChange={this.props.onChange}
            {...this.props.validation}
          >
            {options}
          </select>
        );
      },
    });

    const DateTime = React.createClass({
      DATE_TIME_SEPARATOR: ' ',
      getInitialState: function getInitialState() {
        return this.buildStateFromProps(this.props);
      },
      componentWillReceiveProps: function componentWillReceiveProps(nextProps) {
        this.setState(this.buildStateFromProps(nextProps));
      },
      buildStateFromProps: function buildStateFromProps(props) {
        const value = props.value || defaultDateTime;
        const [date, time] = value.split(this.DATE_TIME_SEPARATOR);
        return {
          date: date,
          time: time,
        };
      },
      handleChange: function handleChange(event) {
        const newState = {};
        newState[event.target.name] = event.target.value;

        this.setState(newState, this.propagateChange);
      },
      propagateChange: function propagateChange() {
        if (this.props.onChange) {
          this.props.onChange({
            target: {
              name: this.props.name || '',
              value: this.getDateTime(),
            },
          });
        }
      },
      getDateTime: function getDateTime() {
        return [this.state.date, this.state.time].join(this.DATE_TIME_SEPARATOR);
      },
      render: function render() {
        return (
          <span>
            <DateText
              name="date"
              value={this.state.date}
              onChange={this.handleChange}
              displayFormat={dateDisplayFormat}
              storageFormat={dateStorageFormat}
              disabled={this.props.disabled}
              validation={this.props.dateValidation}
            />
            <TimeSelect
              name="time"
              value={this.state.time}
              onChange={this.handleChange}
              disabled={this.props.disabled}
              validation={this.props.timeValidation}
            />
          </span>
        );
      },
    });

    const StandardScheduling = React.createClass({
      getCurrentValue: function getCurrentValue() {
        return _.defaults(
          this.props.item[this.props.field.name] || {},
          {
            isScheduled: '0',
            scheduledAt: defaultDateTime,
          }
        );
      },
      handleValueChange: function handleValueChange(event) {
        const oldValue = this.getCurrentValue();
        const newValue = {};
        newValue[event.target.name] = event.target.value;

        return this.props.onValueChange({
          target: {
            name: this.props.field.name,
            value: _.extend({}, oldValue, newValue),
          },
        });
      },
      handleCheckboxChange: function handleCheckboxChange(event) {
        const changeEvent = event;
        changeEvent.target.value = this.isScheduledInput.checked ? '1' : '0';
        return this.handleValueChange(changeEvent);
      },
      isScheduled: function isScheduled() {
        return this.getCurrentValue().isScheduled === '1';
      },
      getDateValidation: function getDateValidation() {
        return {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('noScheduledDateError'),
          'data-parsley-errors-container': '#mailpoet_scheduling',
        };
      },
      render: function render() {
        let schedulingOptions;

        if (this.isScheduled()) {
          schedulingOptions = (
            <span id="mailpoet_scheduling">
              <DateTime
                name="scheduledAt"
                value={this.getCurrentValue().scheduledAt}
                onChange={this.handleValueChange}
                disabled={this.props.field.disabled}
                dateValidation={this.getDateValidation()}
              />
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
              ref={(c) => { this.isScheduledInput = c; }}
              type="checkbox"
              value="1"
              checked={this.isScheduled()}
              disabled={this.props.field.disabled}
              name="isScheduled"
              onChange={this.handleCheckboxChange}
            />

            {schedulingOptions}
          </div>
        );
      },
    });

    let fields = [
      {
        name: 'subject',
        label: MailPoet.I18n.t('subjectLine'),
        tip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
        },
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('segments'),
        tip: MailPoet.I18n.t('segmentsTip'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
        id: 'mailpoet_segments',
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        multiple: true,
        filter: function filter(segment) {
          return !segment.deleted_at;
        },
        getLabel: function getLabel(segment) {
          return `${segment.name} (${parseInt(segment.subscribers, 10).toLocaleString()})`;
        },
        transformChangedValue: function transformChangedValue(segmentIds) {
          const allSegments = this.getItems();
          return _.map(segmentIds, id => _.find(allSegments, segment => segment.id === id));
        },
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('noSegmentsSelectedError'),
        },
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
              'data-parsley-required': true,
            },
          },
          {
            name: 'sender_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
            validation: {
              'data-parsley-required': true,
              'data-parsley-type': 'email',
            },
          },
        ],
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
            placeholder: MailPoet.I18n.t('replyToNamePlaceholder'),
          },
          {
            name: 'reply_to_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
            validation: {
              'data-parsley-type': 'email',
            },
          },
        ],
      },
      {
        name: 'options',
        label: MailPoet.I18n.t('scheduleIt'),
        type: 'reactComponent',
        component: StandardScheduling,
      },
    ];

    fields = Hooks.applyFilters('mailpoet_newsletters_3rd_step_fields', fields);

    return {
      getFields: function getFields() {
        return fields;
      },
      getSendButtonOptions: function getSendButtonOptions(newsletter) {
        const newsletterOptions = newsletter || {};

        const isScheduled = (
          typeof newsletterOptions.options === 'object'
          && newsletterOptions.options.isScheduled === '1'
        );
        const options = {
          value: (isScheduled
            ? MailPoet.I18n.t('schedule')
            : MailPoet.I18n.t('send')),
        };

        if (newsletterOptions.status === 'sent'
            || newsletterOptions.status === 'sending') {
          options.disabled = 'disabled';
        }

        return options;
      },
    };
  }
);
