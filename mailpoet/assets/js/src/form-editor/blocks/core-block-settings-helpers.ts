export type CoreBlock = {
  name: string;
  metadata: Record<string, unknown>;
  settings: Record<string, unknown>;
};

export const getCoreBlockFromList = (
  blocks: CoreBlock[],
  name: string,
): CoreBlock => {
  const block = blocks.find((candidate) => candidate.name === name);
  if (!block) {
    throw new Error(`Missing WordPress core block: ${name}`);
  }
  return block;
};

export const isTypographyTextAlignSupported = (
  metadata: Record<string, unknown>,
): boolean => {
  const supports = metadata.supports;
  if (!supports || typeof supports !== 'object') {
    return false;
  }

  const typography = (supports as Record<string, unknown>).typography;
  return (
    !!typography &&
    typeof typography === 'object' &&
    (typography as Record<string, unknown>).textAlign === true
  );
};
