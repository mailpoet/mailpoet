import React from 'react';
import { action } from '@storybook/addon-actions';
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
        size="small"
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular small')}
        size="small"
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark small')}
        size="small"
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link small')}
        size="small"
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark small')}
        size="small"
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />

    <Heading level={3}>Regular buttons</Heading>
    <p>
      <Button
        onClick={action('light regular')}
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular regular')}
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark regular')}
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link regular')}
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark regular')}
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />

    <Heading level={3}>Large buttons</Heading>
    <p>
      <Button
        onClick={action('light large')}
        size="large"
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular large')}
        size="large"
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark large')}
        size="large"
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link large')}
        size="large"
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark large')}
        size="large"
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />

    <Heading level={3}>Disabled buttons</Heading>
    <p>
      <Button
        onClick={action('light disabled')}
        isDisabled
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular disabled')}
        isDisabled
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark disabled')}
        isDisabled
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link disabled')}
        isDisabled
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark disabled')}
        isDisabled
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />

    <Heading level={3}>Buttons with spinner</Heading>
    <p>
      <Button
        onClick={action('light spinner')}
        withSpinner
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular spinner')}
        withSpinner
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark spinner')}
        withSpinner
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link spinner')}
        withSpinner
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark spinner')}
        withSpinner
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />

    <Heading level={3}>Full width buttons</Heading>
    <p>
      <Button
        onClick={action('light full-width ')}
        isFullWidth
        variant="light"
      >
        Light button
      </Button>
      <Button
        onClick={action('regular full-width ')}
        isFullWidth
      >
        Regular button
      </Button>
      <Button
        onClick={action('dark full-width ')}
        isFullWidth
        variant="dark"
      >
        Dark button
      </Button>
      <Button
        onClick={action('link full-width ')}
        isFullWidth
        variant="link"
      >
        Link button
      </Button>
      <Button
        onClick={action('link-dark full-width ')}
        isFullWidth
        variant="link-dark"
      >
        Link dark button
      </Button>
    </p>
    <br />
  </>
);
