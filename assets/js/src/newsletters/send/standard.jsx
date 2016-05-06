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
          });
        }
      },
      render: function() {
        return (
          <input
            type="text"
            name={this.props.name || 'date'}
            value={this.props.value}
            onChange={this.props.onChange}
            ref="dateInput" />
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
        const [date, time] = props.value.split(this._DATE_TIME_SEPARATOR)
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
          this.props.onChange({
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
              onChange={this.handleChange} />
            <TimeSelect
              name="time"
              value={this.state.time}
              onChange={this.handleChange} />
          </span>
        );
      }
    });

    var StandardScheduling = React.createClass({
      getInitialState: function() {
        return {
          isScheduled: '0',
          scheduledAt: '2016-05-04 14:00:00',
        };
      },
      handleChange: function(event) {
        var newState = {};
        newState[event.target.name] = event.target.value;
        this.setState(newState);
      },
      handleCheckboxChange: function(event) {
        event.target.value = this.refs.isScheduled.checked ? '1' : '0';
        this.handleChange(event);
      },
      isScheduled: function() {
        return this.state.isScheduled === '1';
      },
      render: function() {
        var isChecked = this.isScheduled(),
            schedulingOptions;

        if (isChecked) {
          schedulingOptions = (
            <span>
              <DateTime
                name="scheduledAt"
                value={this.state.scheduledAt}
                onChange={this.handleChange} />
              <span>
                {MailPoet.I18n.t('localTimeIs')} <code>{currentTime}</code>
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
