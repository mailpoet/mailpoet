import React from 'react';
import Tag from './tag';

type Props = {
  children?: React.ReactNode,
  segments: any[]
}

const Tags = ({ children, segments }: Props) => (
  <div className="mailpoet-tags">
    {children}
    {segments.map((segment) => <Tag key={segment.name} dimension="large" variant="list">{segment.name}</Tag>)}
  </div>
);

export default Tags;
