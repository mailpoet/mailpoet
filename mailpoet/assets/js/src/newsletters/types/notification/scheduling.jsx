import _ from 'underscore';
import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { FormFieldSelect as Select } from 'form/fields/select.jsx';
import { Checkbox } from 'common/form/checkbox/checkbox';
import {
  intervalValues,
  timeOfDayValues,
  weekDayValues,
  monthDayValues,
  nthWeekDayValues,
} from 'newsletters/scheduling/common.jsx';
import { Grid } from 'common/grid';

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

const nthWeekDayField = {
  name: 'nthWeekDay',
  values: nthWeekDayValues,
};

const sortValues = (values) =>
  [...values].sort(
    (firstValue, secondValue) => Number(firstValue) - Number(secondValue),
  );

const parseSelectedValues = (value, defaultValue, availableValues) => {
  const availableValueKeys = Object.keys(availableValues);
  const normalizedValue = value === undefined || value === null ? '' : value;
  const selectedValues = `${normalizedValue}`
    .split(',')
    .map((selectedValue) => selectedValue.trim())
    .filter((selectedValue) => availableValueKeys.includes(selectedValue));

  if (selectedValues.length === 0) {
    return [defaultValue];
  }

  return sortValues([...new Set(selectedValues)]);
};

const serializeSelectedValues = (values) => sortValues(values).join(',');

const getFirstSelectedValue = (value, defaultValue) =>
  parseSelectedValues(value, defaultValue, weekDayValues)[0];

const checkboxGridClassName = (columns) =>
  columns === 4 ? 'mailpoet-grid-four-columns' : 'mailpoet-grid-three-columns';

function MultipleCheckboxSelection({
  name,
  values,
  selectedValues,
  onValueChange,
  automationId,
  columns,
}) {
  const normalizedSelectedValues = selectedValues.map((value) => `${value}`);
  return (
    <div className={checkboxGridClassName(columns)}>
      {Object.keys(values).map((value) => (
        <span key={`${name}-${value}`}>
          <Checkbox
            name={name}
            value={value}
            checked={normalizedSelectedValues.includes(value)}
            onCheck={(isChecked) => {
              let nextValues = normalizedSelectedValues;
              if (isChecked) {
                nextValues = normalizedSelectedValues.concat([value]);
              } else if (normalizedSelectedValues.length > 1) {
                nextValues = normalizedSelectedValues.filter(
                  (selectedValue) => selectedValue !== value,
                );
              }
              onValueChange(serializeSelectedValues([...new Set(nextValues)]));
            }}
            automationId={`${automationId}_${value}`}
          >
            {values[value]}
          </Checkbox>
        </span>
      ))}
    </div>
  );
}

MultipleCheckboxSelection.propTypes = {
  name: PropTypes.string.isRequired,
  values: PropTypes.objectOf(PropTypes.string).isRequired,
  selectedValues: PropTypes.arrayOf(PropTypes.string).isRequired,
  onValueChange: PropTypes.func.isRequired,
  automationId: PropTypes.string.isRequired,
  columns: PropTypes.oneOf([3, 4]).isRequired,
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
    const oldValue = this.getCurrentValue();
    const changes = {};
    changes.intervalType = intervalType;
    if (intervalType === 'monthly') {
      changes.monthDay = oldValue.monthDay || '1';
    }
    if (intervalType === 'weekly') {
      changes.weekDay = oldValue.weekDay || '1';
    }
    if (intervalType === 'nthWeekDay') {
      changes.weekDay = getFirstSelectedValue(oldValue.weekDay, '1');
      changes.nthWeekDay = oldValue.nthWeekDay || '1';
    }
    this.handleValueChanges(changes);
  };

  handleTimeOfDayChange = (event) =>
    this.handleValueChanges({ timeOfDay: event.target.value });

  handleWeekDayChange = (event) =>
    this.handleValueChanges({ weekDay: event.target.value });

  handleWeekDaysChange = (weekDay) => this.handleValueChanges({ weekDay });

  handleMonthDaysChange = (monthDay) => this.handleValueChanges({ monthDay });

  handleNthWeekDayChange = (event) =>
    this.handleValueChanges({ nthWeekDay: event.target.value });

  render() {
    const value = this.getCurrentValue();
    let multipleCheckboxSelection;
    let timeOfDaySelection;
    let weekDaySelection;
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

    if (value.intervalType === 'weekly') {
      multipleCheckboxSelection = (
        <MultipleCheckboxSelection
          name="weekDay"
          values={weekDayValues}
          selectedValues={parseSelectedValues(
            value.weekDay,
            '1',
            weekDayValues,
          )}
          onValueChange={this.handleWeekDaysChange}
          automationId="newsletter_week_day"
          columns={3}
        />
      );
    }

    if (value.intervalType === 'nthWeekDay') {
      weekDaySelection = (
        <Select
          field={weekDayField}
          item={this.getCurrentValue()}
          onValueChange={this.handleWeekDayChange}
        />
      );
    }

    if (value.intervalType === 'monthly') {
      multipleCheckboxSelection = (
        <MultipleCheckboxSelection
          name="monthDay"
          values={monthDayValues}
          selectedValues={parseSelectedValues(
            value.monthDay,
            '1',
            monthDayValues,
          )}
          onValueChange={this.handleMonthDaysChange}
          automationId="newsletter_month_day"
          columns={4}
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
        <Grid.CenteredRow>
          <Select
            field={intervalField}
            item={this.getCurrentValue()}
            onValueChange={this.handleIntervalChange}
            automationId="newsletter_interval_type"
          />
          {value.intervalType === 'immediately' && (
            <div>
              <p>{__('after publishing a post', 'mailpoet')}</p>
            </div>
          )}
        </Grid.CenteredRow>
        <div className="mailpoet-gap" />

        {multipleCheckboxSelection && (
          <>
            <div className="mailpoet-grid-column">
              {multipleCheckboxSelection}
            </div>
            <div className="mailpoet-gap" />
          </>
        )}

        <div className="mailpoet-grid-column mailpoet-flex">
          {nthWeekDaySelection}
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
