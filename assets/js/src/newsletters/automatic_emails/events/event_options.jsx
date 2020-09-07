import React from 'react';
import Selection from 'form/fields/selection.jsx';
import _ from 'underscore';
import PropTypes from 'prop-types';

const APIEndpoint = 'automatic_emails';

class EventOptions extends React.Component {
  static getEventOptionsValues(eventOptions) {
    const values = (eventOptions && eventOptions.values) ? eventOptions.values : [];

    return (values) ? values.map((value) => ({
      id: value.id,
      name: value.name,
    })) : values;
  }

  constructor(props) {
    super(props);

    this.handleEventOptionChange = this.handleEventOptionChange.bind(this);
  }

  displayEventOptions() {
    const {
      eventOptions, eventSlug, selected, emailSlug,
    } = this.props;

    if (!eventOptions) return eventOptions;

    const props = {
      field: {
        id: `event_options_${eventSlug}`,
        name: `event_options_${eventSlug}`,
        forceSelect2: true,
        resetSelect2OnUpdate: true,
        values: this.constructor.getEventOptionsValues(eventOptions),
        multiple: eventOptions.multiple || false,
        placeholder: eventOptions.placeholder || false,
        extendSelect2Options: {
          minimumResultsForSearch: Infinity,
        },
        transformChangedValue: (value, valueTextPair) => _.map(
          valueTextPair,
          (data) => ({ id: data.id, name: data.text })
        ),
        selected: () => selected,
      },
      onValueChange: this.handleEventOptionChange,
    };

    if (eventOptions.type === 'remote') {
      props.field = _.extend(props.field, {
        remoteQuery: {
          minimumInputLength: eventOptions.remoteQueryMinimumInputLength || null,
          endpoint: APIEndpoint,
          method: 'get_event_options',
          data: {
            filter: eventOptions.remoteQueryFilter || null,
            email_slug: emailSlug,
            event_slug: eventSlug,
          },
        },
      });
    }

    return (
      <>
        <Selection
          field={props.field}
          onValueChange={props.onValueChange}
        />
        <div className="mailpoet-gap" />
      </>
    );
  }

  handleEventOptionChange(e) {
    const { onValueChange } = this.props;
    if (onValueChange) {
      onValueChange({ eventOptionValue: e.target.value });
    }
  }

  render() {
    return (
      <div>
        <div className="event-option-selection">{this.displayEventOptions()}</div>
      </div>
    );
  }
}

EventOptions.propTypes = {
  selected: PropTypes.array, // eslint-disable-line react/forbid-prop-types
  eventOptions: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  eventSlug: PropTypes.string.isRequired,
  emailSlug: PropTypes.string.isRequired,
  onValueChange: PropTypes.func,
};

EventOptions.defaultProps = {
  eventOptions: null,
  selected: [],
  onValueChange: null,
};

export default EventOptions;
