import { ReactNode } from 'react';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/block-editor/src/components/block-icon/index.js

type Props = {
  icon: ReactNode;
};

export function StepIcon({ icon }: Props): JSX.Element {
  return <span className="block-editor-block-icon">{icon}</span>;
}
