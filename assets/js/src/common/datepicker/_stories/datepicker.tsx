import React, { useState } from 'react';
import { action } from '_storybook/action';
import Datepicker from '../datepicker';
import Heading from '../../typography/heading/heading';
import Icon from '../icon/calendar';

export default {
  title: 'Datepickers',
  component: Datepicker,
};

const DatepickerWrapper = ({
  ...props
}) => {
  const [startDate, setStartDate] = useState(new Date());
  const onChange = (date) => {
    props.onChange(date);
    setStartDate(date);
  };
  return (
    <Datepicker
      {...props} // eslint-disable-line react/jsx-props-no-spreading
      selected={startDate}
      onChange={onChange}
    />
  );
};

export const Datepickers = () => (
  <>
    <Heading level={3}>Small datepicker</Heading>
    <div>
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        dimension="small"
        onChange={action('normal datepicker')}
      />
      <div className="mailpoet-gap" />
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        dimension="small"
        onChange={action('normal datepicker')}
        iconStart={Icon}
      />
      <div className="mailpoet-gap" />
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        dimension="small"
        onChange={action('normal datepicker')}
        iconEnd={Icon}
      />
    </div>
    <br />

    <Heading level={3}>Datepicker</Heading>
    <div>
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        onChange={action('normal datepicker')}
      />
      <div className="mailpoet-gap" />
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        onChange={action('normal datepicker')}
        iconStart={Icon}
      />
      <div className="mailpoet-gap" />
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        onChange={action('normal datepicker')}
        iconEnd={Icon}
      />
    </div>
    <br />

    <Heading level={3}>Datepicker with a minimum date</Heading>
    <div>
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        minDate={new Date()}
        onChange={action('datepicker with a minimum date')}
      />
    </div>
    <br />

    <Heading level={3}>Disabled datepicker</Heading>
    <div>
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        disabled
        onChange={action('disabled datepicker')}
      />
    </div>
    <br />

    <Heading level={3}>Full-width datepicker</Heading>
    <div>
      <DatepickerWrapper
        dateFormat="MMMM d, yyyy"
        isFullWidth
        onChange={action('disabled datepicker')}
        iconStart={Icon}
      />
    </div>
    <br />
  </>
);
