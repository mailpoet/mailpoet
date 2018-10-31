import React from 'react';
import PropTypes from 'prop-types';

import DateText from 'newsletters/send/date_text.jsx';
import TimeSelect from 'newsletters/send/time_select.jsx';

class DateTime extends React.Component {
  state = this.buildStateFromProps(this.props);

  componentWillReceiveProps(nextProps) {
    this.setState(this.buildStateFromProps(nextProps));
  }

  getDateTime = () => [this.state.date, this.state.time].join(this.DATE_TIME_SEPARATOR);

  DATE_TIME_SEPARATOR = ' ';

  buildStateFromProps = (props) => {
    const value = props.value || this.props.defaultDateTime;
    const [date, time] = value.split(this.DATE_TIME_SEPARATOR);
    return {
      date,
      time,
    };
  };

  handleChange = (event) => {
    const newState = {};
    newState[event.target.name] = event.target.value;

    this.setState(newState, this.propagateChange);
  };

  propagateChange = () => {
    if (this.props.onChange) {
      this.props.onChange({
        target: {
          name: this.props.name || '',
          value: this.getDateTime(),
        },
      });
    }
  };

  render() {
    return (
      <span>
        <DateText
          name="date"
          value={this.state.date}
          onChange={this.handleChange}
          displayFormat={this.props.dateDisplayFormat}
          storageFormat={this.props.dateStorageFormat}
          disabled={this.props.disabled}
          validation={this.props.dateValidation}
        />
        <TimeSelect
          name="time"
          value={this.state.time}
          onChange={this.handleChange}
          disabled={this.props.disabled}
          validation={this.props.timeValidation}
          timeOfDayItems={this.props.timeOfDayItems}
        />
      </span>
    );
  }
}

DateTime.propTypes = {
  defaultDateTime: PropTypes.string.isRequired,
  dateDisplayFormat: PropTypes.string.isRequired,
  dateStorageFormat: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  name: PropTypes.string,
  disabled: PropTypes.bool,
  dateValidation: PropTypes.objectOf(PropTypes.string).isRequired,
  timeValidation: PropTypes.any, // eslint-disable-line react/forbid-prop-types
  timeOfDayItems: PropTypes.objectOf(PropTypes.string).isRequired,
};

DateTime.defaultProps = {
  onChange: undefined,
  name: '',
  disabled: false,
  timeValidation: undefined,
};

module.exports = DateTime;
