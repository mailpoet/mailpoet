import { action } from '_storybook/action';
import Button from '../button';
import Heading from '../../typography/heading/heading';
import icon from '../icon/plus';

export default {
  title: 'Buttons',
  component: Button,
};

export function WithIcons() {
  return (
    <>
      <Heading level={3}>Small buttons</Heading>
      <p>
        <Button
          onClick={action('icon start primary small')}
          dimension="small"
          iconStart={icon}
        >
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary small')}
          dimension="small"
          variant="secondary"
          iconStart={icon}
          iconEnd={icon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary small')}
          dimension="small"
          variant="secondary"
          iconStart={icon}
        />
        <Button
          onClick={action('only icon tertiary small')}
          dimension="small"
          variant="tertiary"
          iconStart={icon}
        />
        <Button
          onClick={action('icon end destructive small')}
          dimension="small"
          variant="destructive"
          iconEnd={icon}
        >
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Regular buttons</Heading>
      <p>
        <Button onClick={action('icon start primary regular')} iconStart={icon}>
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary regular')}
          variant="secondary"
          iconStart={icon}
          iconEnd={icon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary regular')}
          variant="secondary"
          iconStart={icon}
        />
        <Button
          onClick={action('only icon tertiary regular')}
          variant="tertiary"
          iconStart={icon}
        />
        <Button
          onClick={action('icon end destructive regular')}
          variant="destructive"
          iconEnd={icon}
        >
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Disabled buttons</Heading>
      <p>
        <Button isDisabled iconStart={icon}>
          Icon start
        </Button>
        <Button isDisabled variant="secondary" iconStart={icon} iconEnd={icon}>
          Both icons
        </Button>
        <Button isDisabled variant="secondary" iconStart={icon} />
        <Button isDisabled variant="tertiary" iconStart={icon} />
        <Button isDisabled variant="destructive" iconEnd={icon}>
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Buttons with spinner</Heading>
      <p>
        <Button withSpinner iconStart={icon}>
          Icon start
        </Button>
        <Button withSpinner variant="secondary" iconStart={icon} iconEnd={icon}>
          Both icons
        </Button>
        <Button withSpinner variant="secondary" iconStart={icon} />
        <Button withSpinner variant="tertiary" iconStart={icon} />
        <Button withSpinner variant="destructive" iconEnd={icon}>
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Full width buttons</Heading>
      <p>
        <Button
          onClick={action('icon start primary full-width')}
          isFullWidth
          iconStart={icon}
        >
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary full-width')}
          isFullWidth
          variant="secondary"
          iconStart={icon}
          iconEnd={icon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary full-width')}
          isFullWidth
          variant="secondary"
          iconStart={icon}
        />
        <Button
          onClick={action('only icon tertiary full-width')}
          isFullWidth
          variant="tertiary"
          iconStart={icon}
        />
        <Button
          onClick={action('icon end destructive full-width')}
          isFullWidth
          variant="destructive"
          iconEnd={icon}
        >
          Icon end
        </Button>
      </p>
      <br />
    </>
  );
}
