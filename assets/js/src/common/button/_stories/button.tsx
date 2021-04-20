import React from 'react';
import { action } from '_storybook/action';
import Button from '../button';
import Heading from '../../typography/heading/heading';

export default {
  title: 'Buttons',
  component: Button,
};

export const WithoutIcons = () => (
  <>
    <Heading level={3}>Small buttons</Heading>
    <p>
      <Button
        onClick={action('light small')}
        dimension="small"
        variant="secondary"
      >
        Secondary button
      </Button>
      <Button
        onClick={action('regular small')}
        dimension="small"
      >
        Primary button
      </Button>
      <Button
        onClick={action('link small')}
        dimension="small"
        variant="tertiary"
      >
        Tertiary button
      </Button>
    </p>
    <br />

    <Heading level={3}>Regular buttons</Heading>
    <p>
      <Button
        onClick={action('light regular')}
        variant="secondary"
      >
        Secondary button
      </Button>
      <Button
        onClick={action('regular regular')}
      >
        Primary button
      </Button>
      <Button
        onClick={action('link regular')}
        variant="tertiary"
      >
        Tertiary button
      </Button>
    </p>
    <br />

    <Heading level={3}>Disabled buttons</Heading>
    <p>
      <Button
        onClick={action('light disabled')}
        isDisabled
        variant="secondary"
      >
        Secondary button
      </Button>
      <Button
        onClick={action('regular disabled')}
        isDisabled
      >
        Primary button
      </Button>
      <Button
        onClick={action('link disabled')}
        isDisabled
        variant="tertiary"
      >
        Tertiary button
      </Button>
    </p>
    <br />

    <Heading level={3}>Buttons with spinner</Heading>
    <p>
      <Button
        onClick={action('light spinner')}
        withSpinner
        variant="secondary"
      >
        Secondary button
      </Button>
      <Button
        onClick={action('regular spinner')}
        withSpinner
      >
        Primary button
      </Button>
      <Button
        onClick={action('link spinner')}
        withSpinner
        variant="tertiary"
      >
        Tertiary button
      </Button>
    </p>
    <br />

    <Heading level={3}>Full width buttons</Heading>
    <p>
      <Button
        onClick={action('light full-width ')}
        isFullWidth
        variant="secondary"
      >
        Secondary button
      </Button>
      <Button
        onClick={action('regular full-width ')}
        isFullWidth
      >
        Primary button
      </Button>
      <Button
        onClick={action('link full-width ')}
        isFullWidth
        variant="tertiary"
      >
        Tertiary button
      </Button>
    </p>
    <br />
  </>
);
