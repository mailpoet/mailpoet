import { ComponentType } from 'react';
import { Step } from '../automation/types';

export type Item = {
  key: string;
  title: () => JSX.Element | string;
  description: (step: Step) => JSX.Element | string;
  keywords: string[];
  icon: ComponentType | JSX.Element;
  isDisabled?: boolean;
};
