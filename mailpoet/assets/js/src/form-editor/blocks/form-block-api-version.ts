export const FORM_BLOCK_API_VERSION = 3;

export const withFormBlockApiVersion = <
  Settings extends Record<string, unknown>,
>(
  settings: Settings,
): Settings & { apiVersion: typeof FORM_BLOCK_API_VERSION } => ({
  ...settings,
  apiVersion: FORM_BLOCK_API_VERSION,
});
