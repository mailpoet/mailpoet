import { Component } from 'react';
import PropTypes from 'prop-types';
import { Select } from 'common/form/select/select.tsx';

// eslint-disable-next-line react/prefer-stateless-function
class TimeSelect extends Component {
  render() {
    const options = Object.keys(this.props.timeOfDayItems).map((value) => (
      <option key={`option-${this.props.timeOfDayItems[value]}`} value={value}>
        {this.props.timeOfDayItems[value]}
      </option>
    ));

    return (
      <Select
        name={this.props.name || 'time'}
        value={this.props.value}
        disabled={this.props.disabled}
        onChange={this.props.onChange}
        isMinWidth
        {...this.props.validation}
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

TimeSelect.defaultProps = {
  name: 'time',
  disabled: false,
  validation: {},
};
TimeSelect.displayName = 'TimeSelect';
export { TimeSelect };
