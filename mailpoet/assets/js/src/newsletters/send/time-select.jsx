import { Component } from 'react';
import PropTypes from 'prop-types';
import { Select } from 'common/form/select/select.tsx';

// eslint-disable-next-line react/prefer-stateless-function
class TimeSelect extends Component {
  render() {
    const {
      onChange,
      timeOfDayItems,
      value,
      disabled = false,
      name = 'time',
      validation = {},
    } = this.props;
    const options = Object.keys(timeOfDayItems).map((val) => (
      <option key={`option-${timeOfDayItems[val]}`} value={val}>
        {timeOfDayItems[val]}
      </option>
    ));
    // If the current value is not in the predefined timeOfDayItems list,
    // create a custom option for it. This handles cases where the scheduled time
    // is set from within the email editor, as the datetime picker allows for
    // setting any time (not just the predefined options). This ensures the select
    // renders the correct value and doesn't fall back to the first predefined time.
    const predefinedTimeKeys = Object.keys(timeOfDayItems);
    const isCustomTime = value && !predefinedTimeKeys.includes(value);
    // To match the format of the predefined time options, we remove the seconds from the custom time value.
    const lastColonIndex = value.lastIndexOf(':');
    const customOptionLabel =
      lastColonIndex > 0 ? value.slice(0, lastColonIndex) : value;
    const customOption = isCustomTime ? (
      <option value={value}>{customOptionLabel}</option>
    ) : null;

    return (
      <Select
        name={name || 'time'}
        value={value}
        disabled={disabled}
        onChange={onChange}
        isMinWidth
        {...validation}
      >
        {customOption}
        {options}
      </Select>
    );
  }
}

TimeSelect.propTypes = {
  timeOfDayItems: PropTypes.objectOf(PropTypes.string).isRequired,
  name: PropTypes.string,
  value: PropTypes.string.isRequired,
  disabled: PropTypes.bool,
  onChange: PropTypes.func.isRequired,
  validation: PropTypes.object, // eslint-disable-line react/forbid-prop-types
};

TimeSelect.displayName = 'TimeSelect';
export { TimeSelect };
