import React from 'react';
import Heading from '../../typography/heading/heading';
import Grid from '..';

export default {
  title: 'Grid',
};

export const Layouts = () => {
  const content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi libero sapien, tristique sollicitudin lobortis id, viverra id libero.';

  return (
    <>
      <Heading level={3}>Column</Heading>
      <Grid.Column>
        {content}
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Grid.Column align="center">
        {content}
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Column - small</Heading>
      <Grid.Column dimension="small">
        {content}
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Two columns</Heading>
      <Grid.TwoColumns>
        <div>{content}</div>
        <div>{content}</div>
      </Grid.TwoColumns>

      <div className="mailpoet-gap" />

      <Heading level={3}>Three columns</Heading>
      <Grid.ThreeColumns>
        <div>{content}</div>
        <div>{content}</div>
        <div>{content}</div>
      </Grid.ThreeColumns>

      <div className="mailpoet-gap" />

      <Heading level={3}>Two columns list</Heading>
      <Grid.Column>
        <Grid.TwoColumnsList>
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
        <Grid.SpaceBetween>
          <div>
            Left
            <br />
            Part
          </div>
          <div>
            Right Part
          </div>
        </Grid.SpaceBetween>
      </Grid.Column>

      <div className="mailpoet-gap" />

      <Heading level={3}>Space between - vertically centered</Heading>
      <Grid.Column>
        <Grid.SpaceBetween verticalAlign="center">
          <div>
            Left
            <br />
            Part
          </div>
          <div>
            Right Part
          </div>
        </Grid.SpaceBetween>
      </Grid.Column>
    </>
  );
};
