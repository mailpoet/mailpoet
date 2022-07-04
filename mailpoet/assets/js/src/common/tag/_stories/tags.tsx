import { Heading } from 'common/typography/heading/heading';
import { Tags } from '../tags';

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
      <Tags segments={segments} dimension="large" />
      <div className="mailpoet-gap" />
      <Tags segments={segments}>
        <span>Prefix: </span>
      </Tags>
      <Heading level={2}>Strings</Heading>
      <Tags strings={strings} dimension="large" variant="good" />
      <div className="mailpoet-gap" />
      <Tags strings={strings} dimension="large" variant="good" isInverted />
    </>
  );
}
