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
import {
  DEFAULT_DAY,
  parseSelectedValues,
  serializeSelectedValues,
} from 'newsletters/scheduling/multi-day';
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
                nextValues = [...normalizedSelectedValues, value];
              } else if (normalizedSelectedValues.length > 1) {
                nextValues = normalizedSelectedValues.filter(
                  (selectedValue) => selectedValue !== value,
                );
              }
              onValueChange(serializeSelectedValues(nextValues));
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
      changes.monthDay = serializeSelectedValues(
        parseSelectedValues(oldValue.monthDay, DEFAULT_DAY, monthDayValues),
      );
    }
    if (intervalType === 'weekly') {
      // Route through parseSelectedValues so weekDay = 0 (Sunday) is preserved
      // -- a plain `oldValue.weekDay || '1'` would treat 0 as missing.
      changes.weekDay = serializeSelectedValues(
        parseSelectedValues(oldValue.weekDay, DEFAULT_DAY, weekDayValues),
      );
    }
    if (intervalType === 'nthWeekDay') {
      changes.weekDay = getFirstSelectedValue(oldValue.weekDay, DEFAULT_DAY);
      changes.nthWeekDay = oldValue.nthWeekDay || DEFAULT_DAY;
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
            DEFAULT_DAY,
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
            DEFAULT_DAY,
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
