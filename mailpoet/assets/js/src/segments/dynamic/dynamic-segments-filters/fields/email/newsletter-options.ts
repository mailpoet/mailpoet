import { __ } from '@wordpress/i18n';
import { MailPoetDate } from '../../../../../date';

import { SelectOption, WindowNewslettersList } from '../../../types';

export type NewsletterOption = SelectOption<number> & {
  tag?: string;
};

type NewsletterOptionGroup = {
  label: string;
  options: NewsletterOption[];
};

type FormatDate = (date: string) => string;

const automationEmailTypes = ['automation', 'automation_transactional'];

const defaultFormatDate: FormatDate = (date) => MailPoetDate.format(date);

function isAutomationEmailType(
  type: WindowNewslettersList[number]['type'],
): boolean {
  return automationEmailTypes.includes(type);
}

function createStandardNewsletterOption(
  newsletter: WindowNewslettersList[number],
  formatDate: FormatDate,
): NewsletterOption {
  return {
    label: newsletter.name,
    tag: newsletter.sent_at
      ? formatDate(newsletter.sent_at)
      : __('Not sent yet', 'mailpoet'),
    value: Number(newsletter.id),
  };
}

// Automation emails track sentAt per send on the queue, not on the newsletter
// entity, so a sent_at-derived tag would always read "Not sent yet" — omit it.
function createAutomationNewsletterOption(
  newsletter: WindowNewslettersList[number],
): NewsletterOption {
  return {
    label: newsletter.name,
    value: Number(newsletter.id),
  };
}

export function getGroupedNewsletterOptions(
  // Typed optional because window.mailpoet_newsletters_list may be missing if
  // the dynamic-segments page hasn't bootstrapped the global yet.
  newslettersList: WindowNewslettersList | undefined,
  formatDate: FormatDate = defaultFormatDate,
): {
  flatOptions: NewsletterOption[];
  groupedOptions: NewsletterOptionGroup[];
} {
  const newsletters = newslettersList ?? [];
  const standardOptions = newsletters
    .filter(({ type }) => type === 'standard')
    .map((newsletter) =>
      createStandardNewsletterOption(newsletter, formatDate),
    );
  const automationOptions = newsletters
    .filter(({ type }) => isAutomationEmailType(type))
    .map((newsletter) => createAutomationNewsletterOption(newsletter));
  const groupedOptions: NewsletterOptionGroup[] = [];

  if (standardOptions.length > 0) {
    groupedOptions.push({
      label: __('Standard emails', 'mailpoet'),
      options: standardOptions,
    });
  }
  if (automationOptions.length > 0) {
    groupedOptions.push({
      label: __('Automation emails', 'mailpoet'),
      options: automationOptions,
    });
  }

  return {
    flatOptions: [...standardOptions, ...automationOptions],
    groupedOptions,
  };
}
