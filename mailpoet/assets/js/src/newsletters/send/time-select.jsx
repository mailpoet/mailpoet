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
      <option key={`option-${timeOfDayItems[value]}`} value={val}>
        {timeOfDayItems[val]}
      </option>
    ));

    return (
      <Select
        name={name || 'time'}
        value={value}
        disabled={disabled}
        onChange={onChange}
        isMinWidth
        {...validation}
      >
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
