import React from 'react';
import { action } from '@storybook/addon-actions';
import Toggle from '../toggle';
import Heading from '../../../typography/heading/heading';
import Grid from '../../../grid';

export default {
  title: 'Form',
  component: Toggle,
};

export const Toggles = () => (
  <>
    <Heading level={3}>Toggles</Heading>
    <Grid.Column dimension="small">
      <Grid.SpaceBetween>
        <label htmlFor="toggle-1">Toggle regular</label>
        <Toggle
          onCheck={action('toggle-1')}
          id="toggle-1"
          name="toggle-1"
        />
      </Grid.SpaceBetween>
      <div className="mailpoet-gap" />
      <Grid.SpaceBetween>
        <label htmlFor="toggle-2">Toggle small</label>
        <Toggle
          onCheck={action('toggle-2')}
          dimension="small"
          id="toggle-2"
          name="toggle-2"
        />
      </Grid.SpaceBetween>
    </Grid.Column>
  </>
);
