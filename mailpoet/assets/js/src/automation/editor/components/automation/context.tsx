import { __unstableUseCompositeState as useCompositeState } from '@wordpress/components';
import { createContext } from '@wordpress/element';

export const AutomationCompositeContext =
  createContext<ReturnType<typeof useCompositeState>>(undefined);
