import { ReactElement } from 'react';
import Heading from '../../typography/heading/heading';
import { Grid } from '..';
import Input from '../../form/input/input';

export default {
  title: 'Grid',
};

export function Layouts(): ReactElement {
  const content =
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi libero sapien, tristique sollicitudin lobortis id, viverra id libero.';

  return (
    <>
      <Heading level={3}>Column</Heading>
      <Grid.Column className="custom-class">{content}</Grid.Column>

      <div className="mailpoet-gap" />

      <Grid.Column align="center">{content}</Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Column - small</Heading>
      <Grid.Column dimension="small">{content}</Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Two columns</Heading>
      <Grid.TwoColumns className="custom-class">
        <div>{content}</div>
        <div>{content}</div>
      </Grid.TwoColumns>

      <div className="mailpoet-gap" />

      <Heading level={3}>Three columns</Heading>
      <Grid.ThreeColumns className="custom-class">
        <div>{content}</div>
        <div>{content}</div>
        <div>{content}</div>
      </Grid.ThreeColumns>

      <div className="mailpoet-gap" />

      <Heading level={3}>Two columns list</Heading>
      <Grid.Column>
        <Grid.TwoColumnsList className="custom-class">
          <div>
            Option 1
            <br />
            Option 2
            <br />
            Option 3
            <br />
            Option 4
            <br />
            Option 5
            <br />
          </div>
        </Grid.TwoColumnsList>
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Space between</Heading>
      <Grid.Column>
        <Grid.SpaceBetween className="custom-class">
          <div>
            Left
            <br />
            Part
          </div>
          <div>Right Part</div>
        </Grid.SpaceBetween>
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Space between - vertically centered</Heading>
      <Grid.Column>
        <Grid.SpaceBetween className="custom-class" verticalAlign="center">
          <div>
            Left
            <br />
            Part
          </div>
          <div>Right Part</div>
        </Grid.SpaceBetween>
      </Grid.Column>

      <Heading level={3}>Centered row</Heading>
      <Grid.Column>
        <Grid.CenteredRow className="custom-class">
          <div>Left</div>
          <Input type="text" />
          <div>Right</div>
        </Grid.CenteredRow>
      </Grid.Column>
    </>
  );
}
