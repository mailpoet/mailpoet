import { action } from '_storybook/action';
import { Button } from '../button';
import { Heading } from '../../typography/heading/heading';
import { plusIcon } from '../icon/plus';

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
          iconStart={plusIcon}
        >
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary small')}
          dimension="small"
          variant="secondary"
          iconStart={plusIcon}
          iconEnd={plusIcon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary small')}
          dimension="small"
          variant="secondary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('only icon tertiary small')}
          dimension="small"
          variant="tertiary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('icon end destructive small')}
          dimension="small"
          variant="destructive"
          iconEnd={plusIcon}
        >
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Regular buttons</Heading>
      <p>
        <Button
          onClick={action('icon start primary regular')}
          iconStart={plusIcon}
        >
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary regular')}
          variant="secondary"
          iconStart={plusIcon}
          iconEnd={plusIcon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary regular')}
          variant="secondary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('only icon tertiary regular')}
          variant="tertiary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('icon end destructive regular')}
          variant="destructive"
          iconEnd={plusIcon}
        >
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Disabled buttons</Heading>
      <p>
        <Button isDisabled iconStart={plusIcon}>
          Icon start
        </Button>
        <Button
          isDisabled
          variant="secondary"
          iconStart={plusIcon}
          iconEnd={plusIcon}
        >
          Both icons
        </Button>
        <Button isDisabled variant="secondary" iconStart={plusIcon} />
        <Button isDisabled variant="tertiary" iconStart={plusIcon} />
        <Button isDisabled variant="destructive" iconEnd={plusIcon}>
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Buttons with spinner</Heading>
      <p>
        <Button withSpinner iconStart={plusIcon}>
          Icon start
        </Button>
        <Button
          withSpinner
          variant="secondary"
          iconStart={plusIcon}
          iconEnd={plusIcon}
        >
          Both icons
        </Button>
        <Button withSpinner variant="secondary" iconStart={plusIcon} />
        <Button withSpinner variant="tertiary" iconStart={plusIcon} />
        <Button withSpinner variant="destructive" iconEnd={plusIcon}>
          Icon end
        </Button>
      </p>
      <br />

      <Heading level={3}>Full width buttons</Heading>
      <p>
        <Button
          onClick={action('icon start primary full-width')}
          isFullWidth
          iconStart={plusIcon}
        >
          Icon start
        </Button>
        <Button
          onClick={action('both icons secondary full-width')}
          isFullWidth
          variant="secondary"
          iconStart={plusIcon}
          iconEnd={plusIcon}
        >
          Both icons
        </Button>
        <Button
          onClick={action('only icon secondary full-width')}
          isFullWidth
          variant="secondary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('only icon tertiary full-width')}
          isFullWidth
          variant="tertiary"
          iconStart={plusIcon}
        />
        <Button
          onClick={action('icon end destructive full-width')}
          isFullWidth
          variant="destructive"
          iconEnd={plusIcon}
        >
          Icon end
        </Button>
      </p>
      <br />
    </>
  );
}
