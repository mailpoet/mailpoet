import { action } from '_storybook/action';
import Button from '../button';
import Heading from '../../typography/heading/heading';

export default {
  title: 'Buttons',
  component: Button,
};

export function WithoutIcons() {
  return (
    <>
      <Heading level={3}>Small buttons</Heading>
      <p>
        <Button onClick={action('primary small')} dimension="small">
          Primary button
        </Button>
        <Button
          onClick={action('secondary small')}
          dimension="small"
          variant="secondary"
        >
          Secondary button
        </Button>
        <Button
          onClick={action('tertiary small')}
          dimension="small"
          variant="tertiary"
        >
          Tertiary button
        </Button>
        <Button
          onClick={action('destructive small')}
          dimension="small"
          variant="destructive"
        >
          Destructive button
        </Button>
      </p>
      <br />

      <Heading level={3}>Regular buttons</Heading>
      <p>
        <Button onClick={action('primary regular')}>Primary button</Button>
        <Button onClick={action('secondary regular')} variant="secondary">
          Secondary button
        </Button>
        <Button onClick={action('tertiary regular')} variant="tertiary">
          Tertiary button
        </Button>
        <Button onClick={action('destructive regular')} variant="destructive">
          Destructive button
        </Button>
      </p>
      <br />

      <Heading level={3}>Disabled buttons</Heading>
      <p>
        <Button isDisabled>Primary button</Button>
        <Button isDisabled variant="secondary">
          Secondary button
        </Button>
        <Button isDisabled variant="tertiary">
          Tertiary button
        </Button>
        <Button isDisabled variant="destructive">
          Destructive button
        </Button>
      </p>
      <br />

      <Heading level={3}>Buttons with spinner</Heading>
      <p>
        <Button withSpinner>Primary button</Button>
        <Button withSpinner variant="secondary">
          Secondary button
        </Button>
        <Button withSpinner variant="tertiary">
          Tertiary button
        </Button>
        <Button withSpinner variant="destructive">
          Destructive button
        </Button>
      </p>
      <br />

      <Heading level={3}>Full width buttons</Heading>
      <p>
        <Button onClick={action('primary full-width')} isFullWidth>
          Primary button
        </Button>
        <Button
          onClick={action('secondary full-width')}
          isFullWidth
          variant="secondary"
        >
          Secondary button
        </Button>
        <Button
          onClick={action('tertiary full-width')}
          isFullWidth
          variant="tertiary"
        >
          Tertiary button
        </Button>
        <Button
          onClick={action('destructive full-width')}
          isFullWidth
          variant="destructive"
        >
          Destructive button
        </Button>
      </p>
      <br />
    </>
  );
}
