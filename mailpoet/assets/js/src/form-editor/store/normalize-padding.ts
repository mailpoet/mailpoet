const addPxToUnitlessLength = (value: unknown): unknown => {
  if (typeof value !== 'string' && typeof value !== 'number') return value;

  const stringValue = `${value}`.trim();
  if (
    stringValue === '' ||
    Number.isNaN(Number(stringValue)) ||
    Number(stringValue) === 0
  ) {
    return value;
  }

  return `${stringValue}px`;
};

export const normalizePadding = (padding: unknown): unknown => {
  if (
    padding === null ||
    padding === undefined ||
    Array.isArray(padding) ||
    typeof padding !== 'object'
  ) {
    return addPxToUnitlessLength(padding);
  }

  const source = padding as Record<string, unknown>;
  let changed = false;
  const normalized: Record<string, unknown> = {};
  Object.keys(source).forEach((key) => {
    const value = source[key];
    const next = addPxToUnitlessLength(value);
    normalized[key] = next;
    if (next !== value) {
      changed = true;
    }
  });
  return changed ? normalized : padding;
};
