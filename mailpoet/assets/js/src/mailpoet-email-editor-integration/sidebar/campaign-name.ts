import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

export const getDefaultEmailName = (): string => __('New Email', 'mailpoet');

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
  // Titles are stored entity-encoded for users without unfiltered_html.
  const names = [name, decodeEntities(name)];
  const followsSubject =
    name === '' ||
    names.includes(defaultName) ||
    names.includes(previousSubject);
  if (!followsSubject) {
    return null;
  }
  return newSubject || defaultName;
}
