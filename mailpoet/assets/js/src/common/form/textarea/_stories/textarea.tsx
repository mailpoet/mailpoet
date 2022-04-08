import Textarea from '../textarea';
import Heading from '../../../typography/heading/heading';

export default {
  title: 'Form',
  component: Textarea,
};

export function Textareas() {
  return (
    <>
      <Heading level={3}>Small textareas</Heading>
      <div>
        <Textarea dimension="small" placeholder="Small textarea value" />
      </div>
      <br />
      <Heading level={3}>Regular textareas</Heading>
      <div>
        <Textarea placeholder="Regular textarea" />
        <div className="mailpoet-gap" />
        <Textarea disabled placeholder="Disabled textarea" />
      </div>
      <br />
      <Heading level={3}>Full-width textareas</Heading>
      <div>
        <Textarea placeholder="Full-width textarea" isFullWidth />
      </div>
      <br />
    </>
  );
}
