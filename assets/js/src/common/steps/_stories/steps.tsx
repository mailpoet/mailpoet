import React from 'react';
import Steps from '../steps';

export default {
  title: 'Steps',
};

export const StepsWithoutTitles = () => (
  <>
    <Steps count={5} current={1} />
    <Steps count={5} current={3} />
    <Steps count={5} current={5} />
  </>
);

export const StepsWithTitles = () => (
  <>
    <Steps count={4} current={1} titles={['First', 'Second', 'Third', 'Fourth']} />
    <Steps count={4} current={3} titles={['First', 'Second', 'Third', 'Fourth']} />
    <Steps count={4} current={4} titles={['First', 'Second', 'Third', 'Fourth']} />
  </>
);
