import { ReactNode } from 'react';
import { __unstableUseCompositeState as useCompositeState } from '@wordpress/components';
import { InserterListboxContext } from './listbox_context';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-listbox/index.js

type Props = {
  children: ReactNode;
};

export function InserterListbox({ children }: Props): JSX.Element {
  const compositeState = useCompositeState({
    shift: true,
    wrap: 'horizontal',
  });
  return (
    <InserterListboxContext.Provider value={compositeState}>
      {children}
    </InserterListboxContext.Provider>
  );
}
