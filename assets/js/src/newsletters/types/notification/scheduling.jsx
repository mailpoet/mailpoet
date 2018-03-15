import _ from 'underscore';
import React from 'react';
import Select from 'form/fields/select.jsx';
import {
  intervalValues,
  timeOfDayValues,
  weekDayValues,
  monthDayValues,
  nthWeekDayValues,
} from 'newsletters/scheduling/common.jsx';

const intervalField = {
  name: 'intervalType',
  values: intervalValues,
};

const timeOfDayField = {
  name: 'timeOfDay',
  values: timeOfDayValues,
};

const weekDayField = {
  name: 'weekDay',
  values: weekDayValues,
};

const monthDayField = {
  name: 'monthDay',
  values: monthDayValues,
};

const nthWeekDayField = {
  name: 'nthWeekDay',
  values: nthWeekDayValues,
};

const NotificationScheduling = React.createClass({
  getCurrentValue: function getCurrentValue() {
    return (this.props.item[this.props.field.name] || {});
  },
  handleValueChange: function handleValueChange(name, value) {
    const oldValue = this.getCurrentValue();
    const newValue = {};

    newValue[name] = value;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue),
      },
    });
  },
  handleIntervalChange: function handleIntervalChange(event) {
    return this.handleValueChange(
      'intervalType',
      event.target.value
    );
  },
  handleTimeOfDayChange: function handleTimeOfDayChange(event) {
    return this.handleValueChange(
      'timeOfDay',
      event.target.value
    );
  },
  handleWeekDayChange: function handleWeekDayChange(event) {
    return this.handleValueChange(
      'weekDay',
      event.target.value
    );
  },
  handleMonthDayChange: function handleMonthDayChange(event) {
    return this.handleValueChange(
      'monthDay',
      event.target.value
    );
  },
  handleNthWeekDayChange: function handleNthWeekDayChange(event) {
    return this.handleValueChange(
      'nthWeekDay',
      event.target.value
    );
  },
  render: function render() {
    const value = this.getCurrentValue();
    let timeOfDaySelection;
    let weekDaySelection;
    let monthDaySelection;
    let nthWeekDaySelection;

    if (value.intervalType !== 'immediately') {
      timeOfDaySelection = (
        <Select
          field={timeOfDayField}
          item={this.getCurrentValue()}
          onValueChange={this.handleTimeOfDayChange}
        />
      );
    }

    if (value.intervalType === 'weekly' || value.intervalType === 'nthWeekDay') {
      weekDaySelection = (
        <Select
          field={weekDayField}
          item={this.getCurrentValue()}
          onValueChange={this.handleWeekDayChange}
        />
      );
    }

    if (value.intervalType === 'monthly') {
      monthDaySelection = (
        <Select
          field={monthDayField}
          item={this.getCurrentValue()}
          onValueChange={this.handleMonthDayChange}
        />
      );
    }

    if (value.intervalType === 'nthWeekDay') {
      nthWeekDaySelection = (
        <Select
          field={nthWeekDayField}
          item={this.getCurrentValue()}
          onValueChange={this.handleNthWeekDayChange}
        />
      );
    }

    return (
      <div>
        <Select
          field={intervalField}
          item={this.getCurrentValue()}
          onValueChange={this.handleIntervalChange}
        />

        {nthWeekDaySelection}
        {monthDaySelection}
        {weekDaySelection}
        {timeOfDaySelection}
      </div>
    );
  },
});

module.exports = NotificationScheduling;
