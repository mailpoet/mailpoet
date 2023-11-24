import { Component, SyntheticEvent } from 'react';

import { Grid } from 'common/grid';
import { DateText } from 'newsletters/send/date-text';
import { TimeSelect } from 'newsletters/send/time-select.jsx';
import { ErrorBoundary } from '../../common';

type DateTimeEvent = SyntheticEvent<HTMLInputElement> & {
  target: EventTarget & {
    name?: string;
    value?: string;
  };
};

type DateTimeProps = {
  value?: string;
  defaultDateTime: string;
  dateDisplayFormat: string;
  dateStorageFormat: string;
  onChange: (date: DateTimeEvent) => void;
  name?: string;
  disabled: boolean;
  dateValidation: {
    'data-parsley-required': boolean;
    'data-parsley-required-message': string;
    'data-parsley-errors-container': string;
  };
  timeOfDayItems: { [key: string]: string };
  maxDate?: Date;
};

type DateTimeState = {
  date?: string;
  time: string;
};

class DateTime extends Component<DateTimeProps, DateTimeState> {
  DATE_TIME_SEPARATOR = ' ';

  constructor(props: DateTimeProps) {
    super(props);

    this.state = this.buildStateFromProps(props);
  }

  componentDidUpdate(prevProps: DateTimeProps) {
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

  buildStateFromProps = (props: DateTimeProps): DateTimeState => {
    const value = props.value || this.props.defaultDateTime;
    const [date, time] = value.split(this.DATE_TIME_SEPARATOR);
    return {
      date,
      time,
    };
  };

  handleChange = (event: DateTimeEvent) => {
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
      } as DateTimeEvent);
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
          <div className="mailpoet-gap" />
          <TimeSelect
            name="time"
            value={this.state.time}
            onChange={this.handleChange}
            disabled={this.props.disabled}
            timeOfDayItems={this.props.timeOfDayItems}
          />
        </ErrorBoundary>
      </Grid.Column>
    );
  }
}

export { DateTime };
