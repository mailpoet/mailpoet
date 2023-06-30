import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { StepMoreControlsType } from '../../../../../../../types/filters';
import { Step as StepData } from '../../../../../../../editor/components/automation/types';
import { OverviewSection, storeName } from '../../../../store';
import { openTab } from '../../../../navigation/open_tab';

export function moreControls(
  element: StepMoreControlsType | null,
  step: StepData,
  context: string,
): StepMoreControlsType {
  const overview = select(storeName).getSection('overview') as OverviewSection;
  if (context !== 'view') {
    return element;
  }
  if (step.type === 'trigger') {
    return {};
  }
  const customControls: StepMoreControlsType = {};
  if (step.key === 'mailpoet:send-email') {
    const email =
      overview.data !== undefined
        ? Object.values(overview.data.emails).find(
            (newsletter) => newsletter.id === step.args?.email_id,
          )
        : undefined;

    customControls.statistics = {
      key: 'statistics',
      control: {
        icon: null,
        title: __('View statistics', 'mailpoet'),
        isDisabled: false,
        onClick: () => {
          window.open(
            `admin.php?page=mailpoet-newsletters#/stats/${
              step.args.email_id as number
            }`,
            '_blank',
          );
        },
      },
      slot: () => null,
    };
    if (email) {
      customControls.preview = {
        key: 'preview',
        control: {
          icon: null,
          title: __('Preview email', 'mailpoet'),
          isDisabled: false,
          onClick: () => {
            window.open(email.previewUrl, '_blank');
          },
        },
        slot: () => null,
      };
    }
  }
  const defaultControls = {
    subscribers: {
      key: 'view-subscribers',
      control: {
        icon: null,
        title: __('View subscribers', 'mailpoet'),
        isDisabled: false,
        onClick: () => {
          openTab('subscribers');
        },
      },
      slot: () => null,
    },
  };

  return {
    ...customControls,
    ...defaultControls,
  };
}
