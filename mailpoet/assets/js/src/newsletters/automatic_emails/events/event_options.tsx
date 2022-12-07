import { Selection } from 'form/fields/selection.jsx';
import _ from 'underscore';
import { withBoundary } from '../../../common';

type EventOptionData = {
  values?: {
    id: string;
    name: string;
  }[];
  multiple: boolean;
  placeholder: string;
  endpoint: string;
};

type Props = {
  eventOptions: EventOptionData;
  eventSlug: string;
  selected: string[];
  onValueChange: (value) => void;
};

function getEventOptionsValues(eventOptions: EventOptionData) {
  const values = eventOptions && eventOptions.values ? eventOptions.values : [];

  return values
    ? values.map((value) => ({
        id: value.id,
        name: value.name,
      }))
    : values;
}

function EventOptions({
  eventOptions,
  eventSlug,
  selected,
  onValueChange,
}: Props) {
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
        transformChangedValue: (_value, valueTextPair) =>
          _.map(valueTextPair, (data) => ({ id: data.id, name: data.text })),
        selected: () => selected,
        getLabel: _.property('name'),
        getValue: _.property('id'),
        validation: {
          'data-parsley-required': true,
        },
      },
      onValueChange: handleEventOptionChange,
      item: {
        action: '',
      },
    };

    if (eventOptions.endpoint === 'product_categories') {
      fieldProps.field.name = 'category_ids';
      fieldProps.item = { action: 'purchasedCategory' };
    }

    if (eventOptions.endpoint === 'products') {
      fieldProps.field.name = 'product_ids';
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
}

EventOptions.displayName = 'EventOptions';
const EventOptionsWithBoundary = withBoundary(EventOptions);
export { EventOptionsWithBoundary as EventOptions };
