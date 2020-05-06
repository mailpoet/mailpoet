import React from 'react';
import { action } from '@storybook/addon-actions';
import Select from '../select';
import Heading from '../../../typography/heading/heading';
import icon from './assets/icon';

export default {
  title: 'Form',
  component: Select,
};

export const SelectBoxes = () => (
  <>
    <Heading level={3}>Small select boxes</Heading>
    <p>
      <Select size="small">
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
      <div className="mailpoet-gap" />
      <Select
        size="small"
        iconStart={icon}
      >
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
    </p>
    <br />
    <Heading level={3}>Regular select boxes</Heading>
    <p>
      <Select>
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
      <div className="mailpoet-gap" />
      <Select iconStart={icon}>
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
    </p>
    <br />
    <Heading level={3}>Full-width select boxes</Heading>
    <p>
      <Select isFullWidth>
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
      <div className="mailpoet-gap" />
      <Select
        isFullWidth
        iconStart={icon}
      >
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
        <option value="3">Option 3</option>
      </Select>
    </p>
    <br />
  </>
);
