import { ComponentType } from 'react';
import { StepIcon } from '../step-icon';

// See: https://github.com/WordPress/gutenberg/blob/af7da80dd54d7fe52772890e2cc1b65073db9655/packages/block-editor/src/components/block-card/index.js

type Props = {
  title: string;
  description: string;
  icon: ComponentType;
};

export function StepCard({ title, description, icon }: Props): JSX.Element {
  return (
    <div className="block-editor-block-card">
      <StepIcon icon={icon} />
      <div className="block-editor-block-card__content">
        <h2 className="block-editor-block-card__title">{title}</h2>
        <span className="block-editor-block-card__description">
          {description}
        </span>
      </div>
    </div>
  );
}
