define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'form/fields/select.jsx'
  ],
  function(
    _,
    React,
    Router,
    MailPoet,
    Select
  ) {

    var intervalField = {
      name: 'intervalType',
      values: {
        'daily': MailPoet.I18n.t('daily'),
        'weekly': MailPoet.I18n.t('weekly'),
        'monthly': MailPoet.I18n.t('monthly'),
        'nthWeekDay': MailPoet.I18n.t('monthlyEvery'),
        'immediately': MailPoet.I18n.t('immediately'),
      },
    };

    var SECONDS_IN_DAY = 86400;
    var TIME_STEP_SECONDS = 3600; // Default: 3600
    var numberOfTimeSteps = SECONDS_IN_DAY / TIME_STEP_SECONDS;
    var timeOfDayValues = _.object(_.map(
      _.times(numberOfTimeSteps, function(step) { return step * TIME_STEP_SECONDS; }),
      function(seconds) {
        var date = new Date(null);
        date.setSeconds(seconds);
        var timeLabel = date.toISOString().substr(11, 5);
        return [seconds, timeLabel];
      }
    ));
    var timeOfDayField = {
      name: 'timeOfDay',
      values: timeOfDayValues,
    };

    var weekDayField = {
      name: 'weekDay',
      values: {
        0: MailPoet.I18n.t('sunday'),
        1: MailPoet.I18n.t('monday'),
        2: MailPoet.I18n.t('tuesday'),
        3: MailPoet.I18n.t('wednesday'),
        4: MailPoet.I18n.t('thursday'),
        5: MailPoet.I18n.t('friday'),
        6: MailPoet.I18n.t('saturday')
      },
    };

    var NUMBER_OF_DAYS_IN_MONTH = 28; // 28 for compatibility with MP2
    var monthDayField = {
      name: 'monthDay',
      values: _.object(_.map(
        _.times(NUMBER_OF_DAYS_IN_MONTH, function(day) { return day; }),
        function(day) {
          var labels = {
            0: MailPoet.I18n.t('first'),
            1: MailPoet.I18n.t('second'),
            2: MailPoet.I18n.t('third')
          },
            label;
          if (labels[day] !== undefined) {
            label = labels[day];
          } else {
            label = MailPoet.I18n.t('nth').replace("%$1d", day + 1);
          }

          return [day, label];
        },
      )),
    };

    var nthWeekDayField = {
      name: 'nthWeekDay',
      values: {
        '1': MailPoet.I18n.t('first'),
        '2': MailPoet.I18n.t('second'),
        '3': MailPoet.I18n.t('third'),
        'L': MailPoet.I18n.t('last'),
      },
    };

    var NotificationScheduling = React.createClass({
      _getCurrentValue: function() {
        return this.props.item[this.props.field.name] || {};
      },
      handleValueChange: function(name, value) {
        var oldValue = this._getCurrentValue(),
            newValue = {};
        newValue[name] = value;

        return this.props.onValueChange({
          target: {
            name: this.props.field.name,
            value: _.extend({}, oldValue, newValue)
          }
        });
      },
      handleIntervalChange: function(event) {
        return this.handleValueChange(
          'intervalType',
          event.target.value
        );
      },
      handleTimeOfDayChange: function(event) {
        return this.handleValueChange(
          'timeOfDay',
          event.target.value
        );
      },
      handleWeekDayChange: function(event) {
        return this.handleValueChange(
          'weekDay',
          event.target.value
        );
      },
      handleMonthDayChange: function(event) {
        return this.handleValueChange(
          'monthDay',
          event.target.value
        );
      },
      handleNthWeekDayChange: function(event) {
        return this.handleValueChange(
          'nthWeekDay',
          event.target.value
        );
      },
      render: function() {
        var value = this._getCurrentValue(),
            timeOfDaySelection,
            weekDaySelection,
            monthDaySelection,
            nthWeekDaySelection;


        if (value.intervalType !== 'immediately') {
          timeOfDaySelection = (
            <Select
              field={timeOfDayField}
              item={this._getCurrentValue()}
              onValueChange={this.handleTimeOfDayChange} />
          );
        }

        if (value.intervalType === 'weekly'
            || value.intervalType === 'nthWeekDay') {
          weekDaySelection = (
            <Select
              field={weekDayField}
              item={this._getCurrentValue()}
              onValueChange={this.handleWeekDayChange} />
          );
        }

        if (value.intervalType === 'monthly') {
          monthDaySelection = (
            <Select
              field={monthDayField}
              item={this._getCurrentValue()}
              onValueChange={this.handleMonthDayChange} />
          );
        }

        if (value.intervalType === 'nthWeekDay') {
          nthWeekDaySelection = (
            <Select
              field={nthWeekDayField}
              item={this._getCurrentValue()}
              onValueChange={this.handleNthWeekDayChange} />
          );
        }

        return (
          <div>
            <Select
              field={intervalField}
              item={this._getCurrentValue()}
              onValueChange={this.handleIntervalChange} />

            {nthWeekDaySelection}
            {monthDaySelection}
            {weekDaySelection}
            {timeOfDaySelection}
          </div>
        );
      },
    });

    return NotificationScheduling;
  }
);
