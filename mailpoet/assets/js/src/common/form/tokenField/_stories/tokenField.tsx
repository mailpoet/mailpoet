import { action } from '_storybook/action';
import { FormTokenField } from '@wordpress/components';
import { TokenField } from '../tokenField';
import { Heading } from '../../../typography/heading/heading';
import { Grid } from '../../../grid';

export default {
  title: 'Form',
  component: TokenField,
};

const suggestedValues = ['Option 1', 'Option 3', 'Option 4'];

const selectedValues: FormTokenField.Value[] = [{ value: 'Option 2' }];

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
