export type ExcludeFromArchiveValue =
  | string
  | number
  | boolean
  | null
  | undefined;

export const ARCHIVE_VISIBLE_OPTION_VALUE = '0';
export const ARCHIVE_HIDDEN_OPTION_VALUE = '1';

export function isNewsletterShownInArchive(
  excludeFromArchive: ExcludeFromArchiveValue,
): boolean {
  return (
    excludeFromArchive !== ARCHIVE_HIDDEN_OPTION_VALUE &&
    excludeFromArchive !== true
  );
}

export function getExcludeFromArchiveOptionValue(
  showInArchive: boolean,
): typeof ARCHIVE_VISIBLE_OPTION_VALUE | typeof ARCHIVE_HIDDEN_OPTION_VALUE {
  return showInArchive
    ? ARCHIVE_VISIBLE_OPTION_VALUE
    : ARCHIVE_HIDDEN_OPTION_VALUE;
}

export function isNewsletterShownInArchiveFromEditorValue(
  showInArchive?: boolean,
): boolean {
  return showInArchive !== false;
}
