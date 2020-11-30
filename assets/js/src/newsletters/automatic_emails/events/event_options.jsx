import React from 'react';
import Selection from 'form/fields/selection.jsx';
import _ from 'underscore';
import PropTypes from 'prop-types';

const APIEndpoint = 'automatic_emails';

function getEventOptionsValues(eventOptions) {
  const values = (eventOptions && eventOptions.values) ? eventOptions.values : [];

  return (values) ? values.map((value) => ({
    id: value.id,
    name: value.name,
  })) : values;
}

export const EventOptions = ({
  eventOptions,
  eventSlug,
  selected,
  emailSlug,
  onValueChange,
}) => {
  function handleEventOptionChange(e) {
    if (onValueChange) {
      onValueChange({ eventOptionValue: e.target.value });
    }
  }

  function displayEventOptions() {
    if (!eventOptions) return eventOptions;

    const fieldProps = {
      field: {
        id: `event_options_${eventSlug}`,
        name: `event_options_${eventSlug}`,
        forceSelect2: true,
        resetSelect2OnUpdate: true,
        values: getEventOptionsValues(eventOptions),
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
      onValueChange: handleEventOptionChange,
    };

    if (eventOptions.type === 'remote') {
      fieldProps.field = _.extend(fieldProps.field, {
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
          field={fieldProps.field}
          onValueChange={fieldProps.onValueChange}
        />
        <div className="mailpoet-gap" />
      </>
    );
  }

  return (
    <div>
      <div className="event-option-selection">{displayEventOptions()}</div>
    </div>
  );
};

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
