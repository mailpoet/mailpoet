import React from 'react';
import Selection from 'form/fields/selection.jsx';
import _ from 'underscore';

type EventOptions = {
  values?: {
    id: string;
    name: string;
  }[];
  multiple: boolean;
  placeholder: string;
  endpoint: string;
}

type Props = {
  eventOptions: EventOptions;
  eventSlug: string;
  selected: string[];
  onValueChange: (value) => void;
}

function getEventOptionsValues(eventOptions: EventOptions) {
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
  onValueChange,
}: Props) => {
  function handleEventOptionChange(e) {
    if (onValueChange) {
      onValueChange({ eventOptionValue: e.target.value });
    }
  }

  function displayEventOptions() {
    if (!eventOptions) return eventOptions;

    const fieldProps = {
      field: {
        name: `event_options_${eventSlug}`,
        forceSelect2: true,
        endpoint: eventOptions.endpoint,
        resetSelect2OnUpdate: true,
        values: getEventOptionsValues(eventOptions),
        multiple: eventOptions.multiple || false,
        placeholder: eventOptions.placeholder || false,
        transformChangedValue: (value, valueTextPair) => _.map(
          valueTextPair,
          (data) => ({ id: data.id, name: data.text })
        ),
        selected: () => selected,
        getLabel: undefined,
        getValue: undefined,
      },
      onValueChange: handleEventOptionChange,
      item: {
        action: '',
      },
    };

    if (eventOptions.endpoint === 'product_categories') {
      fieldProps.field.getLabel = _.property('cat_name');
      fieldProps.field.name = 'category_id';
      fieldProps.field.getValue = _.property('term_id');
      fieldProps.item = { action: 'purchasedCategory' };
    }

    if (eventOptions.endpoint === 'products') {
      fieldProps.field.getLabel = _.property('title');
      fieldProps.field.getValue = _.property('ID');
      fieldProps.field.name = 'product_id';
      fieldProps.item = { action: 'purchasedProduct' };
    }

    return (
      <>
        <Selection
          field={fieldProps.field}
          onValueChange={fieldProps.onValueChange}
          item={fieldProps.item}
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

EventOptions.defaultProps = {
  eventOptions: null,
  selected: [],
  onValueChange: null,
};

export default EventOptions;
