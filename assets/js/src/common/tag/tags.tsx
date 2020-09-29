import React from 'react';
import Tag from './tag';

type Props = {
  children?: React.ReactNode,
  dimension?: 'large',
  segments: any[]
}

const Tags = ({ children, dimension, segments }: Props) => (
  <div className="mailpoet-tags">
    {children}
    {segments.map((segment) => <Tag key={segment.name} dimension={dimension} variant="list">{segment.name}</Tag>)}
  </div>
);

export default Tags;
