import { ComponentProps } from 'react';
import {
  Button,
  __unstableCompositeItem as CompositeItem,
} from '@wordpress/components';
import { forwardRef, useContext } from '@wordpress/element';
import { InserterListboxContext } from './listbox_context';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-listbox/item.js

type Props = ComponentProps<typeof CompositeItem> & { isFirst: boolean };

export const InserterListboxItem = forwardRef<HTMLButtonElement, Props>(
  ({ isFirst, children, ...props }, ref): JSX.Element => {
    const state = useContext(InserterListboxContext);
    return (
      <CompositeItem ref={ref} state={state} role="option" focusable {...props}>
        {(htmlProps) => {
          const propsWithTabIndex = {
            ...htmlProps,
            tabIndex: isFirst ? 0 : htmlProps.tabIndex,
          };
          return <Button {...propsWithTabIndex}>{children}</Button>;
        }}
      </CompositeItem>
    );
  },
);
