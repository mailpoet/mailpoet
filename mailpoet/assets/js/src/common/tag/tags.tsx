import React from 'react';
import Tag from './tag';

type Segment = {
  name: string;
}

type Props = {
  children?: React.ReactNode;
  dimension?: 'large';
  segments?: Segment[];
  strings?: string[];
}

const Tags = ({
  children,
  dimension,
  segments,
  strings,
}: Props) => (
  <div className="mailpoet-tags">
    {children}
    {segments && segments.map((segment) => <Tag key={segment.name} dimension={dimension} variant="list">{segment.name}</Tag>)}
    {strings && strings.map((string) => <Tag key={string} dimension={dimension} variant="list">{string}</Tag>)}
  </div>
);

export default Tags;
