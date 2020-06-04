import React, { useState } from 'react';
import { action } from '@storybook/addon-actions';
import DatePicker from '../datepicker';
import Heading from '../../typography/heading/heading';

export default {
  title: 'Datepickers',
  component: DatePicker,
};

const DatePickerWrapper = ({
  ...props
}) => {
  const [startDate, setStartDate] = useState(new Date());
  const onChange = (date) => {
    props.onChange(date);
    setStartDate(date);
  };
  return (
    <DatePicker
      {...props} // eslint-disable-line react/jsx-props-no-spreading
      selected={startDate}
      onChange={onChange}
    />
  );
};

export const Datepickers = () => (
  <>
    <Heading level={3}>Datepicker</Heading>
    <p>
      <DatePickerWrapper
        dateFormat="MMMM d, yyyy"
        onChange={action('normal datepicker')}
      />
    </p>
    <br />

    <Heading level={3}>Datepicker with a minimum date</Heading>
    <p>
      <DatePickerWrapper
        dateFormat="MMMM d, yyyy"
        minDate={new Date()}
        onChange={action('datepicker with a minimum date')}
      />
    </p>
    <br />

    <Heading level={3}>Disabled datepicker</Heading>
    <p>
      <DatePickerWrapper
        dateFormat="MMMM d, yyyy"
        disabled
        onChange={action('disabled datepicker')}
      />
    </p>
    <br />
  </>
);
