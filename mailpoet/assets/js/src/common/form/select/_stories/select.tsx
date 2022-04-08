import Select from '../select';
import Heading from '../../../typography/heading/heading';
import icon from './assets/icon';

export default {
  title: 'Form',
  component: Select,
};

export function NativeSelect() {
  return (
    <>
      <Heading level={3}>Min-width select boxes</Heading>
      <div>
        <Select isMinWidth>
          <option value="1">Opt 1</option>
          <option value="2">Opt 2</option>
          <option value="3">Opt 3</option>
        </Select>
        <div className="mailpoet-gap" />
        <Select isMinWidth iconStart={icon}>
          <option value="1">Opt 1</option>
          <option value="2">Opt 2</option>
          <option value="3">Opt 3</option>
        </Select>
      </div>
      <br />
      <Heading level={3}>Small select boxes</Heading>
      <div>
        <Select dimension="small">
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
        <div className="mailpoet-gap" />
        <Select dimension="small" iconStart={icon}>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
      </div>
      <br />
      <Heading level={3}>Regular select boxes</Heading>
      <div>
        <Select>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
        <div className="mailpoet-gap" />
        <Select iconStart={icon}>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
        <div className="mailpoet-gap" />
        <Select disabled>
          <option value="1">Disabled</option>
        </Select>
      </div>
      <br />
      <Heading level={3}>Full-width select boxes</Heading>
      <div>
        <Select isFullWidth>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
        <div className="mailpoet-gap" />
        <Select isFullWidth iconStart={icon}>
          <option value="1">Option 1</option>
          <option value="2">Option 2</option>
          <option value="3">Option 3</option>
        </Select>
      </div>
      <br />
    </>
  );
}
