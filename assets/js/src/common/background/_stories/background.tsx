import React from 'react';
import Background from '../background';

export default {
  title: 'Background',
  component: Background,
};

export const Backgrounds = () => (
  <>
    <Background color="#f00" />
    <h1>Red background</h1>
  </>
);
