type CampaignNameForSubjectArgs = {
  name: string;
  previousSubject: string;
  newSubject: string;
  defaultName: string;
};

/**
 * Returns the campaign name to set when the subject changes, or null to keep
 * the current name. The name follows the subject until the user picks one.
 */
export function getCampaignNameForSubject({
  name,
  previousSubject,
  newSubject,
  defaultName,
}: CampaignNameForSubjectArgs): string | null {
  const followsSubject =
    name === '' || name === defaultName || name === previousSubject;
  if (!followsSubject) {
    return null;
  }
  return newSubject || defaultName;
}
