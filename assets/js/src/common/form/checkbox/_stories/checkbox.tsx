import React from 'react';
import { action } from '@storybook/addon-actions';
import Checkbox from '../checkbox';
import CheckboxGroup from '../group';
import Heading from '../../../typography/heading/heading';

export default {
  title: 'Form',
  component: Checkbox,
};

const options = [
  { value: 1, label: 'Group option 1' },
  { value: 2, label: 'Group option 2' },
];

export const Checkboxes = () => (
  <>
    <Heading level={3}>Inline individual checkboxes</Heading>
    <div>
      <Checkbox
        onChange={action('checkbox-individual-1')}
        name="story"
        value="1"
      >
        Option 1
      </Checkbox>
      <Checkbox
        onChange={action('checkbox-individual-2')}
        name="story"
        value="2"
      >
        Option 2
      </Checkbox>
    </div>
    <br />

    <Heading level={3}>Full-width individual checkboxes</Heading>
    <div>
      <Checkbox
        isFullWidth
        onChange={action('checkbox-full-individual-1')}
        name="story-full"
        value="1"
      >
        Option 1
      </Checkbox>
      <Checkbox
        isFullWidth
        onChange={action('checkbox-full-individual-2')}
        name="story-full"
        value="2"
      >
        Option 2
      </Checkbox>
    </div>
    <br />

    <Heading level={3}>Inline group checkboxes</Heading>
    <CheckboxGroup
      defaultValue={[1, 2]}
      name="story-group"
      onChange={action('checkbox-group')}
      options={options}
    />
    <br />

    <Heading level={3}>Full-width group checkboxes</Heading>
    <CheckboxGroup
      name="story-group-full"
      onChange={action('checkbox-group-full')}
      options={options}
      isFullWidth
    />
  </>
);
