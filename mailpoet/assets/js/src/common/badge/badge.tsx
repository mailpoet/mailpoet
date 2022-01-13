import React from 'react';

type Props = {
  title: string;
};

const Badge = ({ title }: Props) => (
  <span className="mailpoet-badge">{title}</span>
);

export default Badge;
