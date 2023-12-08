import { action } from '_storybook/action';
import { TokenField } from '../token-field';
import { Heading } from '../../../typography/heading/heading';
import { Grid } from '../../../grid';

export default {
  title: 'Form',
  component: TokenField,
};

const suggestedValues = ['Option 1', 'Option 3', 'Option 4'];

const selectedValues = [{ value: 'Option 2' }];

export function TokenFields() {
  return (
    <>
      <Heading level={3}>Token Fields</Heading>
      <Grid.Column dimension="small">
        <Grid.SpaceBetween>
          <TokenField
            onChange={action('onChange')}
            suggestedValues={suggestedValues}
            selectedValues={selectedValues}
            label="Add Option"
          />
        </Grid.SpaceBetween>
      </Grid.Column>
    </>
  );
}
