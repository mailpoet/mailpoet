import React from 'react';
import { action } from '_storybook/action';
import Button from '../button';
import Heading from '../../typography/heading/heading';
import icon from '../icon/plus';

export default {
  title: 'Buttons',
  component: Button,
};

export const WithIcons = () => (
  <>
    <Heading level={3}>Small buttons</Heading>
    <p>
      <Button
        onClick={action('only icon small')}
        dimension="small"
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start small')}
        dimension="small"
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons small')}
        dimension="small"
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link small')}
        dimension="small"
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />

    <Heading level={3}>Regular buttons</Heading>
    <p>

      <Button
        onClick={action('only icon regular')}
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start regular')}
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons regular')}
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link regular')}
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />

    <Heading level={3}>Large buttons</Heading>
    <p>
      <Button
        onClick={action('only icon large')}
        dimension="large"
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start large')}
        dimension="large"
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons large')}
        dimension="large"
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link large')}
        dimension="large"
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />

    <Heading level={3}>Disabled buttons</Heading>
    <p>
      <Button
        onClick={action('only icon disabled')}
        isDisabled
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start disabled')}
        isDisabled
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons disabled')}
        isDisabled
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link disabled')}
        isDisabled
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />

    <Heading level={3}>Buttons with spinner</Heading>
    <p>
      <Button
        onClick={action('only icon spinner')}
        withSpinner
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start spinner')}
        withSpinner
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons spinner')}
        withSpinner
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link spinner')}
        withSpinner
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />

    <Heading level={3}>Full width buttons</Heading>
    <p>
      <Button
        onClick={action('only icon full-width')}
        isFullWidth
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start full-width')}
        isFullWidth
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('both icons full-width')}
        isFullWidth
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link full-width')}
        isFullWidth
        variant="link"
        iconStart={icon}
      />
    </p>
    <br />
  </>
);
