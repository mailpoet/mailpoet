import React from 'react';
import List from '../list';
import Heading from '../../heading/heading';

export default {
  title: 'Typography',
  component: List,
};

export const Lists = () => (
  <>
    <Heading level={3}>Unordered list</Heading>
    <List>
      <li>Item 1</li>
      <li>Item 2</li>
      <li>Item 3</li>
      <li>Item 4</li>
      <li>Item 5</li>
    </List>

    <div className="mailpoet-gap" />

    <Heading level={3}>Ordered list</Heading>
    <List isOrdered>
      <li>Item 1</li>
      <li>Item 2</li>
      <li>Item 3</li>
      <li>Item 4</li>
      <li>Item 5</li>
    </List>
  </>
);
