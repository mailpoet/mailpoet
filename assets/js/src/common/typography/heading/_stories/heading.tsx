import React from 'react';
import Heading from '../heading';

export default {
  title: 'Typography',
  component: Heading,
};

export const Headings = () => (
  <>
    <Heading level={0}>Level 0 heading</Heading>
    <Heading level={1}>Level 1 heading</Heading>
    <Heading level={2}>Level 2 heading</Heading>
    <Heading level={3}>Level 3 heading</Heading>
    <Heading level={4}>Level 4 heading</Heading>
    <Heading level={5}>Level 5 heading</Heading>
  </>
);
