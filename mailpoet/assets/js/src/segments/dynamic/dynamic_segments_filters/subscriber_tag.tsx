import { Hooks } from 'wp-js-hooks';

import { WordpressRoleFormItem } from '../types';
import { DynamicSegmentsPremiumBanner } from '../premium_banner';

export function validateSubscriberTag(
  formItems: WordpressRoleFormItem,
): boolean {
  return Hooks.applyFilters(
    'mailpoet_dynamic_segments_filter_subscriber_tag_validate',
    false,
    formItems,
  );
}

type Props = {
  filterIndex: number;
};

export function SubscriberTag({ filterIndex }: Props): JSX.Element {
  return Hooks.applyFilters(
    'mailpoet_dynamic_segments_filter_subscriber_tag',
    <DynamicSegmentsPremiumBanner />,
    filterIndex,
  );
}
