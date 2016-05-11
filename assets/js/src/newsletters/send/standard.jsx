define(
  [
    'react',
    'jquery',
    'mailpoet',
    'form/fields/checkbox.jsx',
    'form/fields/select.jsx',
    'form/fields/text.jsx',
  ],
  function(
    React,
    jQuery,
    MailPoet,
    Checkbox,
    Select,
    Text
  ) {

    var settings = window.mailpoet_settings || {},
        currentTime = window.mailpoet_current_time || '00:00',
        defaultDateTime = window.mailpoet_current_date + ' ' + '00:00:00';
        timeOfDayItems = window.mailpoet_schedule_time_of_day;

    var isScheduledField = {
      name: 'isScheduled',
    };

    var DateText = React.createClass({
      componentDidMount: function() {
        var $element = jQuery(this.refs.dateInput);
        if ($element.datepicker) {
          $element.datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function() {
              jQuery(this).change();
            }
          });

          this.datepickerInitialized = true;
        }
      },
      componentWillUnmount: function() {
        if (this.datepickerInitialized) {
          jQuery(this.refs.dateInput).datepicker('destroy');
        }
      },
      render: function() {
        return (
          <input
            type="text"
            size="10"
            name={this.props.name || 'date'}
            value={this.props.value}
            onChange={this.props.onChange}
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
        return this.props.item[this.props.field.name] || {};
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
          'data-parsley-pattern': '[0-9]{4}-[0-9]{2}-[0-9]{2}',
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
          var name = segment.name;
          if (segment.subscribers > 0) {
            name += ' (%$1s)'.replace('%$1s', parseInt(segment.subscribers).toLocaleString());
          }
          return name;
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

    return function(newsletter) {
      return fields;
    };
  }
);
