import { __experimentalGetCoreBlocks } from '@wordpress/block-library';

type CoreBlock = {
  name: string;
  metadata: Record<string, unknown>;
  settings: Record<string, unknown>;
};

type SettingsReset = {
  name: string;
  examples: null;
  deprecated: null;
  save: () => null;
};

const getCoreBlock = (name: string): CoreBlock => {
  const block = __experimentalGetCoreBlocks().find(
    (candidate) => candidate.name === name,
  );
  if (!block) {
    throw new Error(`Missing WordPress core block: ${name}`);
  }
  return block;
};

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
