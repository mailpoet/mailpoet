import React from 'react';
import { action } from '@storybook/addon-actions';
import Radio from '../radio';
import RadioGroup from '../group';
import Heading from '../../../typography/heading/heading';

export default {
  title: 'Form',
  component: Radio,
};

const options = [
  { value: 1, label: 'Group option 1' },
  { value: 2, label: 'Group option 2' },
];

export const Radios = () => (
  <>
    <Heading level={3}>Inline individual radios</Heading>
    <div>
      <Radio
        onCheck={action('radio-individual-1')}
        name="story"
        value="1"
      >
        Option 1
      </Radio>
      <Radio
        onCheck={action('radio-individual-2')}
        name="story"
        value="2"
      >
        Option 2
      </Radio>
    </div>
    <br />

    <Heading level={3}>Full-width individual radios</Heading>
    <div>
      <Radio
        isFullWidth
        onCheck={action('radio-full-individual-1')}
        name="story-full"
        value="1"
      >
        Option 1
      </Radio>
      <Radio
        isFullWidth
        onCheck={action('radio-full-individual-2')}
        name="story-full"
        value="2"
      >
        Option 2
      </Radio>
    </div>
    <br />

    <Heading level={3}>Inline group radios</Heading>
    <RadioGroup
      defaultValue={1}
      name="story-group"
      onChange={action('radio-group')}
      options={options}
    />
    <br />

    <Heading level={3}>Full-width group radios</Heading>
    <RadioGroup
      name="story-group-full"
      onChange={action('radio-group-full')}
      options={options}
      isFullWidth
    />
  </>
);
