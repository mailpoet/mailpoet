import { Component } from 'react';
import PropTypes from 'prop-types';

import { Grid } from 'common/grid';
import { DateText } from 'newsletters/send/date_text.jsx';
import { TimeSelect } from 'newsletters/send/time_select.jsx';
import { ErrorBoundary } from '../../common';

class DateTime extends Component {
  DATE_TIME_SEPARATOR = ' ';

  constructor(props) {
    super(props);

    this.state = this.buildStateFromProps(props);
  }

  componentDidUpdate(prevProps) {
    if (
      this.props.value !== prevProps.value ||
      this.props.defaultDateTime !== prevProps.defaultDateTime
    ) {
      setImmediate(() => {
        this.setState(this.buildStateFromProps(this.props));
      });
    }
  }

  getDateTime = () =>
    [this.state.date, this.state.time].join(this.DATE_TIME_SEPARATOR);

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
      <Grid.Column className="mailpoet-datetime-container">
        <ErrorBoundary>
          <DateText
            name="date"
            value={this.state.date}
            onChange={this.handleChange}
            displayFormat={this.props.dateDisplayFormat}
            storageFormat={this.props.dateStorageFormat}
            disabled={this.props.disabled}
            validation={this.props.dateValidation}
            maxDate={this.props.maxDate}
          />
          <TimeSelect
            name="time"
            value={this.state.time}
            onChange={this.handleChange}
            disabled={this.props.disabled}
            validation={this.props.timeValidation}
            timeOfDayItems={this.props.timeOfDayItems}
          />
        </ErrorBoundary>
      </Grid.Column>
    );
  }
}

DateTime.propTypes = {
  value: PropTypes.string,
  defaultDateTime: PropTypes.string.isRequired,
  dateDisplayFormat: PropTypes.string.isRequired,
  dateStorageFormat: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  name: PropTypes.string,
  disabled: PropTypes.bool,
  dateValidation: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  timeValidation: PropTypes.any, // eslint-disable-line react/forbid-prop-types
  timeOfDayItems: PropTypes.objectOf(PropTypes.string).isRequired,
  maxDate: PropTypes.instanceOf(Date),
};

DateTime.defaultProps = {
  onChange: undefined,
  name: '',
  disabled: false,
  timeValidation: undefined,
  value: undefined,
  maxDate: null,
};

export { DateTime };
