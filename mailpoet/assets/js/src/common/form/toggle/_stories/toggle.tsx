import { action } from '_storybook/action';
import Toggle from '../toggle';
import Heading from '../../../typography/heading/heading';
import { Grid } from '../../../grid';

export default {
  title: 'Form',
  component: Toggle,
};

export function Toggles() {
  return (
    <>
      <Heading level={3}>Toggles</Heading>
      <Grid.Column dimension="small">
        <Grid.SpaceBetween>
          <label htmlFor="toggle-1">Toggle regular</label>
          <Toggle onCheck={action('toggle-1')} id="toggle-1" name="toggle-1" />
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
        <div className="mailpoet-gap" />
        <Grid.SpaceBetween>
          <label htmlFor="toggle-3">Toggle disabled</label>
          <Toggle
            disabled
            onCheck={action('toggle-3')}
            id="toggle-3"
            name="toggle-3"
          />
        </Grid.SpaceBetween>
      </Grid.Column>
    </>
  );
}
