import { Component } from 'react';
import PropTypes from 'prop-types';
import _ from 'underscore';

import { MailPoet } from 'mailpoet';
import { Selection } from 'form/fields/selection.jsx';
import { FormFieldText } from 'form/fields/text.jsx';
import { timeDelayValues } from 'newsletters/scheduling/common.jsx';
import { Grid } from 'common/grid';

const defaultAfterTimeType = 'immediate';
const defaultAfterTimeNumber = 1;
const defaultAfterTimeNumberForMinutes = 30;
const defaultAfterTimeNumberInputFieldSize = 3;

class EventScheduling extends Component {
  constructor(props) {
    super(props);
    const { item, event } = this.props;

    this.handleChange = this.handleChange.bind(this);
    const afterTimeType =
      item.afterTimeType || event.defaultAfterTimeType || defaultAfterTimeType;
    const eventDefaultAfterTimeNumber =
      afterTimeType === 'minutes'
        ? defaultAfterTimeNumberForMinutes
        : defaultAfterTimeNumber;
    const afterTimeNumber = item.afterTimeNumber || eventDefaultAfterTimeNumber;

    this.state = {
      afterTimeType,
      afterTimeNumber,
    };

    // Propagate change when default values were applied
    if (
      item.afterTimeNumber !== afterTimeNumber ||
      item.afterTimeType !== afterTimeType
    ) {
      this.propagateChange(this.state);
    }
  }

  handleChange(e, property) {
    let { value } = e.target;
    if (property === 'afterTimeNumber') {
      value = parseInt(e.target.value, 10);
      value = Number.isNaN(value) ? null : value;
    }
    const data = { [property]: value };

    // Reset afterTimeNumber to default when switching between minutes and other types
    const { afterTimeType } = this.state;
    if (property === 'afterTimeType' && afterTimeType !== value) {
      if (afterTimeType === 'minutes') {
        data.afterTimeNumber = defaultAfterTimeNumber;
      }
      if (value === 'minutes') {
        data.afterTimeNumber = defaultAfterTimeNumberForMinutes;
      }
    }
    this.setState(data, this.propagateChange(data));
  }

  displayAfterTimeTypeOptions() {
    const { event } = this.props;
    let values = timeDelayValues;
    if (event.timeDelayValues) {
      values = Object.entries(event.timeDelayValues).reduce(
        (accumulator, [key, value]) => {
          accumulator[key] = value.text;
          return accumulator;
        },
        {},
      );
    }
    const { afterTimeType } = this.state;
    const props = {
      field: {
        id: 'scheduling_time_interval',
        name: 'scheduling_time_interval',
        forceSelect2: true,
        values: _.map(values, (name, id) => ({ name, id })),
        extendSelect2Options: {
          minimumResultsForSearch: Infinity,
        },
        selected: () => afterTimeType,
      },
      onValueChange: _.partial(this.handleChange, _, 'afterTimeType'),
    };

    return (
      <Selection field={props.field} onValueChange={props.onValueChange} />
    );
  }

  displayAfterTimeNumberField() {
    const { afterTimeNumberSize, event } = this.props;
    const { afterTimeType, afterTimeNumber } = this.state;
    if (afterTimeType === 'immediate') return null;
    if (
      event.timeDelayValues &&
      event.timeDelayValues[afterTimeType] &&
      !event.timeDelayValues[afterTimeType].displayAfterTimeNumberField
    )
      return null;

    const props = {
      field: {
        id: 'scheduling_time_duration',
        name: 'scheduling_time_duration',
        defaultValue: afterTimeNumber ? afterTimeNumber.toString() : '',
        size: afterTimeNumberSize,
        validation: {
          'data-parsley-required': true,
          'data-parsley-errors-container': '.mailpoet-form-errors',
          'data-parsley-scheduled-at': MailPoet.I18n.t(
            'emailCanBeScheduledUpToFiveYears',
          ),
        },
      },
      item: {},
      onValueChange: _.partial(this.handleChange, _, 'afterTimeNumber'),
    };

    return (
      <FormFieldText
        field={props.field}
        item={props.item}
        onValueChange={props.onValueChange}
      />
    );
  }

  propagateChange(data) {
    const { onValueChange } = this.props;
    if (!onValueChange) return;

    onValueChange(data);
  }

  render() {
    const { event } = this.props;
    return (
      <>
        <h4> {MailPoet.I18n.t('whenToSendMail')} </h4>

        <Grid.CenteredRow className="mailpoet-re-engagement-scheduling">
          {this.displayAfterTimeNumberField()}
          {this.displayAfterTimeTypeOptions()}

          {event.afterDelayText && <p>{event.afterDelayText}</p>}
        </Grid.CenteredRow>

        <div className="mailpoet-form-errors" />
        <div className="mailpoet-gap" />
        {event.schedulingReadMoreLink && (
          <>
            <a
              href={event.schedulingReadMoreLink.link}
              target="_blank"
              rel="noopener noreferrer"
              className="event-scheduling-read-more-link"
            >
              {event.schedulingReadMoreLink.text}
            </a>
            <div className="mailpoet-gap" />
          </>
        )}
      </>
    );
  }
}

EventScheduling.propTypes = {
  item: PropTypes.shape({
    afterTimeType: PropTypes.string.isRequired,
    afterTimeNumber: PropTypes.number,
  }).isRequired,
  afterTimeNumberSize: PropTypes.number,
  onValueChange: PropTypes.func,
  event: PropTypes.shape({
    defaultAfterTimeType: PropTypes.string,
    afterDelayText: PropTypes.string,
    timeDelayValues: PropTypes.objectOf(
      PropTypes.shape({
        text: PropTypes.string,
        displayAfterTimeNumberField: PropTypes.bool,
      }),
    ),
    schedulingReadMoreLink: PropTypes.shape({
      link: PropTypes.string.isRequired,
      text: PropTypes.string.isRequired,
    }),
  }).isRequired,
};

EventScheduling.defaultProps = {
  afterTimeNumberSize: defaultAfterTimeNumberInputFieldSize,
  onValueChange: null,
};
EventScheduling.displayName = 'EventScheduling';
export { EventScheduling };
