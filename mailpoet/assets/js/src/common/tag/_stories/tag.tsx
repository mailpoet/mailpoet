import { Heading } from 'common/typography/heading/heading';
import { Tag } from '../tag';

export default {
  title: 'Tag',
};

export function Tags() {
  return (
    <>
      <Heading level={1}>Tags</Heading>
      <Tag dimension="large">Opened</Tag>
      &nbsp;
      <Tag dimension="large" isInverted>
        Clicked
      </Tag>
      <br />
      <Tag>Opened</Tag>
      &nbsp;
      <Tag isInverted>Clicked</Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="average">
        Average
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="average" isInverted>
        Average
      </Tag>
      <br />
      <Tag variant="average">Average</Tag>
      &nbsp;
      <Tag variant="average" isInverted>
        Average
      </Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="good">
        Good
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="good" isInverted>
        Good
      </Tag>
      <br />
      <Tag variant="good">Good</Tag>
      &nbsp;
      <Tag variant="good" isInverted>
        Good
      </Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="excellent">
        Excellent
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="excellent" isInverted>
        Excellent
      </Tag>
      <br />
      <Tag variant="excellent">Excellent</Tag>
      &nbsp;
      <Tag variant="excellent" isInverted>
        Excellent
      </Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="critical">
        Critical
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="critical" isInverted>
        Critical
      </Tag>
      <br />
      <Tag variant="critical">Critical</Tag>
      &nbsp;
      <Tag variant="critical" isInverted>
        Critical
      </Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="wordpress">
        WordPress
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="wordpress" isInverted>
        WordPress
      </Tag>
      <br />
      <Tag variant="wordpress">WordPress</Tag>
      &nbsp;
      <Tag variant="wordpress" isInverted>
        WordPress
      </Tag>
      <div className="mailpoet-gap" />
      <Tag dimension="large" variant="list">
        My default list
      </Tag>
      &nbsp;
      <Tag dimension="large" variant="list" isInverted>
        My default list
      </Tag>
      <br />
      <Tag variant="list">My default list</Tag>
      &nbsp;
      <Tag variant="list" isInverted>
        My default list
      </Tag>
    </>
  );
}
