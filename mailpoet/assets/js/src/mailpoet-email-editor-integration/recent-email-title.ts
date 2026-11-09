type RecentEmailTitleArgs = {
  name: string;
  subject: string;
  defaultName: string;
  defaultSubject: string;
};

/**
 * Returns the label for an email in the "Recent" preset list. A name or subject
 * that was never changed from its default says nothing about the email, so it
 * is left out.
 */
export function getRecentEmailTitle({
  name,
  subject,
  defaultName,
  defaultSubject,
}: RecentEmailTitleArgs): string {
  const usefulName = name === defaultName ? '' : name;
  const usefulSubject = subject === defaultSubject ? '' : subject;
  if (usefulName === usefulSubject) {
    return usefulSubject;
  }
  return [usefulName, usefulSubject].filter(Boolean).join(' – ');
}
