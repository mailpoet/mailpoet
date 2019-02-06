import _ from 'underscore';
import React from 'react';
import PropTypes from 'prop-types';
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

class NotificationScheduling extends React.Component {
  getCurrentValue = () => this.props.item[this.props.field.name] || {};

  handleValueChange = (name, value) => {
    const oldValue = this.getCurrentValue();
    const newValue = {};

    newValue[name] = value;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue),
      },
    });
  };

  handleIntervalChange = (event) => {
    const intervalType = event.target.value;
    this.handleValueChange('intervalType', intervalType);
    if (intervalType === 'monthly') {
      this.handleValueChange('monthDay', '1');
    }
  }

  handleTimeOfDayChange = event => this.handleValueChange('timeOfDay', event.target.value);

  handleWeekDayChange = event => this.handleValueChange('weekDay', event.target.value);

  handleMonthDayChange = event => this.handleValueChange('monthDay', event.target.value);

  handleNthWeekDayChange = event => this.handleValueChange('nthWeekDay', event.target.value);

  render() {
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
          automationId="newsletter_interval_type"
        />

        {nthWeekDaySelection}
        {monthDaySelection}
        {weekDaySelection}
        {timeOfDaySelection}
      </div>
    );
  }
}

NotificationScheduling.propTypes = {
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};

export default NotificationScheduling;
