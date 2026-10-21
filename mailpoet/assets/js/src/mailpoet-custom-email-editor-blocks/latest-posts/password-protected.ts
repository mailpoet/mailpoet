// Mirrors BlockPostQuery::PASSWORD_PROTECTED_* on the server.
export type PasswordProtectedMode = 'title_only' | 'exclude' | 'full';

export const DEFAULT_PASSWORD_PROTECTED: PasswordProtectedMode = 'title_only';

/**
 * Whether a post that needs a password keeps its content to itself. The email
 * answers the same way, so what the canvas shows is what goes out.
 */
export const hidesPostContent = (mode?: PasswordProtectedMode): boolean =>
  (mode ?? DEFAULT_PASSWORD_PROTECTED) !== 'full';

type RecordWithProtection = { content?: { protected?: boolean } };

/**
 * The REST API marks a post that has a password, whoever asks for it and
 * whether or not they may read the content.
 */
export const isProtectedRecord = (
  record?: RecordWithProtection | null,
): boolean => record?.content?.protected === true;
