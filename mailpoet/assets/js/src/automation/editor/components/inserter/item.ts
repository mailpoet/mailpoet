import { ComponentType } from 'react';
import { Step } from '../automation/types';
import { StepRenderContext } from '../../store/types';

export type Item = {
  key: string;
  title: (
    step: Step | null,
    context: StepRenderContext,
  ) => JSX.Element | string;
  description: (step: Step, context: StepRenderContext) => JSX.Element | string;
  keywords: string[];
  icon: ComponentType | JSX.Element;
  isDisabled?: boolean;
};
