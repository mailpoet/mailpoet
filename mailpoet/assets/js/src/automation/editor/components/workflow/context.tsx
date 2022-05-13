import { __unstableUseCompositeState as useCompositeState } from '@wordpress/components';
import { createContext } from '@wordpress/element';

export const WorkflowCompositeContext =
  createContext<ReturnType<typeof useCompositeState>>(undefined);
