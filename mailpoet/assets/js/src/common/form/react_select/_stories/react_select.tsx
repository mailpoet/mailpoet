import { action } from '_storybook/action';
import Select from '../react_select';
import Heading from '../../../typography/heading/heading';
import icon from './assets/icon';

export default {
  title: 'Form',
  component: Select,
};

export function ReactSelect() {
  const options = [
    {
      value: 'chocolate',
      label: 'Chocolate',
    },
    {
      value: 'strawberry',
      label: 'Strawberry',
      tag: 'Category',
    },
    {
      value: 'vanilla',
      label: 'Vanilla',
      count: 13,
    },
    {
      value: 'long',
      label:
        'Very very very very very very very very very very very very long option',
      tag: 'long',
      count: 1234,
    },
  ];

  return (
    <>
      <Heading level={3}>Small select boxes</Heading>
      <div>
        <Select
          dimension="small"
          options={options}
          placeholder="Select single value"
          onChange={action('small single')}
        />
        <div className="mailpoet-gap" />
        <Select
          dimension="small"
          iconStart={icon}
          options={options}
          isMulti
          placeholder="Select multiple values"
          onChange={action('small multi')}
        />
      </div>
      <br />
      <Heading level={3}>Regular select boxes</Heading>
      <div>
        <Select
          options={options}
          placeholder="Select single value"
          onChange={action('regular single')}
        />
        <div className="mailpoet-gap" />
        <Select
          iconStart={icon}
          options={options}
          defaultValue={options[2]}
          onChange={action('regular single')}
        />
        <div className="mailpoet-gap" />
        <Select
          options={options}
          placeholder="Select multiple values"
          isMulti
          onChange={action('regular multi')}
        />
        <div className="mailpoet-gap" />
        <Select
          iconStart={icon}
          options={options}
          isMulti
          defaultValue={[options[2], options[1]]}
          onChange={action('regular multi')}
        />
        <div className="mailpoet-gap" />
        <Select
          disabled
          options={options}
          placeholder="Disabled select"
          onChange={action('regular single')}
        />
      </div>
      <br />
      <Heading level={3}>Full-width select boxes</Heading>
      <div>
        <Select
          isFullWidth
          options={options}
          isMulti
          placeholder="Select multiple values"
          onChange={action('full-width multi')}
        />
        <div className="mailpoet-gap" />
        <Select
          isFullWidth
          iconStart={icon}
          options={options}
          placeholder="Select single value"
          onChange={action('full-width single')}
        />
      </div>
      <br />
    </>
  );
}
