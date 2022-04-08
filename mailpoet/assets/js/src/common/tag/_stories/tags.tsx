import Heading from 'common/typography/heading/heading';
import Tags from '../tags';

export default {
  title: 'Tags',
};

const segments = [
  { name: 'List 1' },
  { name: 'Default list' },
  { name: 'Unconfirmed' },
];

export function Segments() {
  return (
    <>
      <Heading level={1}>Segments</Heading>
      <Tags segments={segments} dimension="large" />
      <div className="mailpoet-gap" />
      <Tags segments={segments}>
        <span>Prefix: </span>
      </Tags>
    </>
  );
}
