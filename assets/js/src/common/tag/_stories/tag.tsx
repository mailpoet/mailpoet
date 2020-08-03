import React from 'react';
import Heading from 'common/typography/heading/heading';
import Tag from '../tag';

export default {
  title: 'Tag',
};

export const Tags = () => (
  <>
    <Heading level={1}>Tags</Heading>
    <Tag>Opened</Tag>
    &nbsp;
    <Tag isInverted>Clicked</Tag>

    <div className="mailpoet-gap" />

    <Tag variant="average">Average</Tag>
    &nbsp;
    <Tag variant="average" isInverted>Average</Tag>

    <div className="mailpoet-gap" />

    <Tag variant="good">Good</Tag>
    &nbsp;
    <Tag variant="good" isInverted>Good</Tag>

    <div className="mailpoet-gap" />

    <Tag variant="excellent">Excellent</Tag>
    &nbsp;
    <Tag variant="excellent" isInverted>Excellent</Tag>
  </>
);
