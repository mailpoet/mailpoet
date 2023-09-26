import { __unstableUseCompositeState as useCompositeState } from '@wordpress/components';
import { createContext } from '@wordpress/element';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-listbox/context.js

export const InserterListboxContext =
  createContext<ReturnType<typeof useCompositeState>>(undefined);
