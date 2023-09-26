export const extractEmailDomain = (email: string): string =>
  String(email || '')
    .trim()
    .split('@')
    .pop()
    .toLowerCase();
