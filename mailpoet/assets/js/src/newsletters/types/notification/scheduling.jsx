import _ from 'underscore';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { FormFieldSelect as Select } from 'form/fields/select.jsx';
import {
  intervalValues,
  timeOfDayValues,
  weekDayValues,
  monthDayValues,
  nthWeekDayValues,
} from 'newsletters/scheduling/common.jsx';

const customValues = {
  ...intervalValues,
  immediately: `${MailPoet.I18n.t('immediately')} ${MailPoet.I18n.t(
    'postNotificationNewsletterAfterValueText',
  )}`,
};

const intervalField = {
  name: 'intervalType',
  values: customValues,
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

class NotificationScheduling extends Component {
  getCurrentValue = () => this.props.item[this.props.field.name] || {};

  handleValueChanges = (changes) => {
    const oldValue = this.getCurrentValue();

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, changes),
      },
    });
  };

  handleIntervalChange = (event) => {
    const intervalType = event.target.value;
    const changes = {};
    changes.intervalType = intervalType;
    if (intervalType === 'monthly') {
      changes.monthDay = '1';
    }
    this.handleValueChanges(changes);
  };

  handleTimeOfDayChange = (event) =>
    this.handleValueChanges({ timeOfDay: event.target.value });

  handleWeekDayChange = (event) =>
    this.handleValueChanges({ weekDay: event.target.value });

  handleMonthDayChange = (event) =>
    this.handleValueChanges({ monthDay: event.target.value });

  handleNthWeekDayChange = (event) =>
    this.handleValueChanges({ nthWeekDay: event.target.value });

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

    if (
      value.intervalType === 'weekly' ||
      value.intervalType === 'nthWeekDay'
    ) {
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
        <div className="mailpoet-gap" />

        <div className="mailpoet-grid-column mailpoet-flex">
          {nthWeekDaySelection}
          {monthDaySelection}
          {weekDaySelection}
          {timeOfDaySelection}
        </div>

        {value.intervalType !== 'immediately' && (
          <div className="mailpoet-gap" />
        )}
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

export { NotificationScheduling };
