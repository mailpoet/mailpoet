import { Heading } from 'common/typography/heading/heading';
import { SegmentTags, StringTags } from '../tags';

export default {
  title: 'Tags',
};

const segments = [
  { name: 'List 1' },
  { name: 'Default list' },
  { name: 'Unconfirmed' },
];

const strings = ['First', 'Second', 'Third tag'];

export function Segments() {
  return (
    <>
      <Heading level={1}>Tags</Heading>
      <Heading level={2}>Segments</Heading>
      <SegmentTags segments={segments} dimension="large" />
      <div className="mailpoet-gap" />
      <SegmentTags segments={segments}>
        <span>Prefix: </span>
      </SegmentTags>
      <Heading level={2}>Strings</Heading>
      <StringTags strings={strings} dimension="large" variant="good" />
      <div className="mailpoet-gap" />
      <StringTags
        strings={strings}
        dimension="large"
        variant="good"
        isInverted
      />
    </>
  );
}
