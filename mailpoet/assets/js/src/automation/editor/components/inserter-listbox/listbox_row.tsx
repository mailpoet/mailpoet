import { ComponentProps } from 'react';
import { __unstableCompositeGroup as CompositeGroup } from '@wordpress/components';
import { forwardRef, useContext } from '@wordpress/element';
import { InserterListboxContext } from './listbox_context';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-listbox/row.js

type Props = ComponentProps<typeof CompositeGroup>;

export const InserterListboxRow = forwardRef<HTMLDivElement, Props>(
  (props, ref): JSX.Element => {
    const state = useContext(InserterListboxContext);
    return (
      <CompositeGroup state={state} role="presentation" ref={ref} {...props} />
    );
  },
);
