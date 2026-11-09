type RecentEmailTitleArgs = {
  name: string;
  subject: string;
  defaultName: string;
};

/**
 * Returns the label for an email in the "Recent" preset list. A name that was
 * never changed from the default says nothing about the email, so it is left out.
 */
export function getRecentEmailTitle({
  name,
  subject,
  defaultName,
}: RecentEmailTitleArgs): string {
  const usefulName = name === defaultName ? '' : name;
  if (usefulName === subject) {
    return subject;
  }
  return [usefulName, subject].filter(Boolean).join(' – ');
}
