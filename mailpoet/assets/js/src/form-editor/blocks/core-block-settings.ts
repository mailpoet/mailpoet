import { __experimentalGetCoreBlocks } from '@wordpress/block-library';

import type { CoreBlock } from './core-block-settings-helpers';
import {
  getCoreBlockFromList,
  isTypographyTextAlignSupported,
} from './core-block-settings-helpers';

type SettingsReset = {
  name: string;
  examples: null;
  deprecated: null;
  save: () => null;
};

const getCoreBlock = (name: string): CoreBlock =>
  getCoreBlockFromList(__experimentalGetCoreBlocks(), name);

export const isCoreBlockTypographyTextAlignSupported = (
  name: string,
): boolean => isTypographyTextAlignSupported(getCoreBlock(name).metadata);

export const getCoreBlockSettings = (
  name: string,
  settingsReset: SettingsReset,
  supportsReset: Record<string, boolean>,
): Record<string, unknown> => {
  const block = getCoreBlock(name);
  return {
    ...block.metadata,
    ...block.settings,
    ...settingsReset,
    category: 'design',
    supports: {
      ...((block.metadata.supports as Record<string, unknown> | undefined) ??
        {}),
      ...supportsReset,
    },
  };
};
