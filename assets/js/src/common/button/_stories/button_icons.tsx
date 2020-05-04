import React from 'react';
import { action } from '@storybook/addon-actions';
import Button from '../button';
import Heading from '../../typography/heading/heading';
import icon from './assets/icon';

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
        size="small"
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start small')}
        size="small"
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('icon end small')}
        size="small"
        variant="dark"
        iconEnd={icon}
      >
        Icon end
      </Button>
      <Button
        onClick={action('both icons small')}
        size="small"
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link small')}
        size="small"
        variant="link"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start link small')}
        size="small"
        variant="link-dark"
        iconStart={icon}
      >
        Link icon
      </Button>
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
        onClick={action('icon end regular')}
        variant="dark"
        iconEnd={icon}
      >
        Icon end
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
      <Button
        onClick={action('icon start link regular')}
        variant="link-dark"
        iconStart={icon}
      >
        Link icon
      </Button>
    </p>
    <br />

    <Heading level={3}>Large buttons</Heading>
    <p>
      <Button
        onClick={action('only icon large')}
        size="large"
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start large')}
        size="large"
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('icon end large')}
        size="large"
        variant="dark"
        iconEnd={icon}
      >
        Icon end
      </Button>
      <Button
        onClick={action('both icons large')}
        size="large"
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link large')}
        size="large"
        variant="link"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start link large')}
        size="large"
        variant="link-dark"
        iconStart={icon}
      >
        Link icon
      </Button>
    </p>
    <br />

    <Heading level={3}>Loading buttons</Heading>
    <p>
      <Button
        onClick={action('only icon loading')}
        isLoading
        variant="light"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start loading')}
        isLoading
        iconStart={icon}
      >
        Icon start
      </Button>
      <Button
        onClick={action('icon end loading')}
        isLoading
        variant="dark"
        iconEnd={icon}
      >
        Icon end
      </Button>
      <Button
        onClick={action('both icons loading')}
        isLoading
        variant="light"
        iconStart={icon}
        iconEnd={icon}
      >
        Both icons
      </Button>
      <Button
        onClick={action('only icon link loading')}
        isLoading
        variant="link"
        iconStart={icon}
      />
      <Button
        onClick={action('icon start link loading')}
        isLoading
        variant="link-dark"
        iconStart={icon}
      >
        Link icon
      </Button>
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
        onClick={action('icon end full-width')}
        isFullWidth
        variant="dark"
        iconEnd={icon}
      >
        Icon end
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
      <Button
        onClick={action('icon start link full-width')}
        isFullWidth
        variant="link-dark"
        iconStart={icon}
      >
        Link icon
      </Button>
    </p>
    <br />
  </>
);
