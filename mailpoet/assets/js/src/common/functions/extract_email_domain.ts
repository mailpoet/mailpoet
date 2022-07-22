export const extractEmailDomain = (email: string): string =>
  String(email || '')
    .split('@')
    .pop()
    .toLowerCase();
