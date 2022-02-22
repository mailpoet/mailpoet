import React from 'react';
import Badge from '../badge';

export default {
  title: 'Badge',
  component: Badge,
};

export function Badges() {
  return (
    <>
      <p>
        <Badge title="Must-Have" />
      </p>
      <p>
        <Badge title="Premium" />
      </p>
    </>
  );
}
