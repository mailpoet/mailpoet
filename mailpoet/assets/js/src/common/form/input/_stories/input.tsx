import Input from '../input';
import Heading from '../../../typography/heading/heading';
import icon from './assets/icon';

export default {
  title: 'Form',
  component: Input,
};

export function Inputs() {
  return (
    <>
      <Heading level={3}>Small inputs</Heading>
      <div>
        <Input type="text" dimension="small" placeholder="Small input value" />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Small input with iconStart"
          dimension="small"
          iconStart={icon}
        />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Small input with iconEnd"
          dimension="small"
          iconEnd={icon}
        />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Small input with both icons"
          dimension="small"
          iconStart={icon}
          iconEnd={icon}
        />
      </div>
      <br />
      <Heading level={3}>Regular inputs</Heading>
      <div>
        <Input type="text" placeholder="Regular input" />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Regular input with iconStart"
          iconStart={icon}
        />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Regular input with iconEnd"
          iconEnd={icon}
        />
        <div className="mailpoet-gap" />
        <Input
          type="text"
          placeholder="Regular input with both icons"
          iconStart={icon}
          iconEnd={icon}
        />
        <div className="mailpoet-gap" />
        <Input disabled type="text" placeholder="Disabled input" />
      </div>
      <br />
      <Heading level={3}>Full-width inputs</Heading>
      <div>
        <Input type="text" placeholder="Full-width input" isFullWidth />
        <Input
          type="text"
          placeholder="Full-width input with iconStart"
          isFullWidth
          iconStart={icon}
        />
        <Input
          type="text"
          placeholder="Full-width input with iconEnd"
          isFullWidth
          iconEnd={icon}
        />
        <Input
          type="text"
          placeholder="Full-width input with both icons"
          isFullWidth
          iconStart={icon}
          iconEnd={icon}
        />
      </div>
      <br />
    </>
  );
}
