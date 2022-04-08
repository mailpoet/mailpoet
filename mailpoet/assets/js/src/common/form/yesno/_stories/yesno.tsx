import { action } from '_storybook/action';
import YesNo from '../yesno';
import Heading from '../../../typography/heading/heading';
import { Grid } from '../../../grid';

export default {
  title: 'Form',
  component: YesNo,
};

export function YesNos() {
  return (
    <>
      <Heading level={3}>YesNos</Heading>
      <Grid.Column dimension="small">
        <Grid.SpaceBetween verticalAlign="center">
          <div>YesNo</div>
          <YesNo onCheck={action('yesno-1')} name="yesno-1" />
        </Grid.SpaceBetween>
        <div className="mailpoet-gap" />
        <Grid.SpaceBetween verticalAlign="center">
          <div>YesNo with error</div>
          <YesNo showError onCheck={action('yesno-2')} name="yesno-2" />
        </Grid.SpaceBetween>
        <div className="mailpoet-gap" />
        <Grid.SpaceBetween verticalAlign="center">
          <div>YesNo disabled</div>
          <YesNo disabled onCheck={action('yesno-3')} name="yesno-3" />
        </Grid.SpaceBetween>
      </Grid.Column>
    </>
  );
}
