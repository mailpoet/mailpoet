import { ComponentType } from 'react';
import { Icon } from '@wordpress/components';

// See: https://github.com/WordPress/gutenberg/blob/af7da80dd54d7fe52772890e2cc1b65073db9655/packages/block-editor/src/components/block-icon/index.js

type Props = {
  icon: ComponentType | JSX.Element;
};

export function StepIcon({ icon }: Props): JSX.Element {
  return (
    <span className="block-editor-block-icon">
      <Icon icon={icon} />
    </span>
  );
}
