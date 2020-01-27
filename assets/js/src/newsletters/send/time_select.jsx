import React from 'react';
import PropTypes from 'prop-types';

class TimeSelect extends React.Component { // eslint-disable-line react/prefer-stateless-function
  render() {
    const options = Object.keys(this.props.timeOfDayItems).map(
      (value) => (
        <option
          key={`option-${this.props.timeOfDayItems[value]}`}
          value={value}
        >
          { this.props.timeOfDayItems[value] }
        </option>
      )
    );

    return (
      <select
        name={this.props.name || 'time'}
        value={this.props.value}
        disabled={this.props.disabled}
        onChange={this.props.onChange}
        {...this.props.validation} // eslint-disable-line react/jsx-props-no-spreading
      >
        {options}
      </select>
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

TimeSelect.defaultProps = {
  name: 'time',
  disabled: false,
  validation: {},
};

export default TimeSelect;
