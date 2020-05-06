import React from 'react';
import { action } from '@storybook/addon-actions';
import Input from '../input';
import Heading from '../../../typography/heading/heading';
import icon from './assets/icon';

export default {
  title: 'Form',
  component: Input,
};

export const Inputs = () => (
  <>
    <Heading level={3}>Small inputs</Heading>
    <p>
      <Input
        type="text"
        size="small"
        placeholder="Small input value"
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Small input with iconStart"
        size="small"
        iconStart={icon}
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Small input with iconEnd"
        size="small"
        iconEnd={icon}
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Small input with both icons"
        size="small"
        iconStart={icon}
        iconEnd={icon}
      />
    </p>
    <br />
    <Heading level={3}>Regular inputs</Heading>
    <p>
      <Input
        type="text"
        placeholder="Regular input"
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Regular input with iconStart"
        iconStart={icon}
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Regular input with iconEnd"
        iconEnd={icon}
      />
      <div className="mailpoet-gap" />
      <Input
        type="text"
        placeholder="Regular input with both icons"
        iconStart={icon}
        iconEnd={icon}
      />
    </p>
    <br />
    <Heading level={3}>Full-width inputs</Heading>
    <p>
      <Input
        type="text"
        placeholder="Full-width input"
        isFullWidth
      />
      <Input
        type="text"
        placeholder="Full-width input with iconStart"
        isFullWidth
        iconStart={icon}
      />
      <Input
        type="text"
        placeholder="Full-width input with iconEnd"
        isFullWidth
        iconEnd={icon}
      />
      <Input
        type="text"
        placeholder="Full-width input with both icons"
        isFullWidth
        iconStart={icon}
        iconEnd={icon}
      />
    </p>
    <br />
  </>
);
