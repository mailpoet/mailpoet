import { PanelBody, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PremiumModal } from 'common/premium_modal';
import { Hooks } from 'wp-js-hooks';
import { storeName } from '../../../../../editor/store';

export function GoogleAnalyticsPanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({ selectedStep: select(storeName).getSelectedStep() }),
    [],
  );

  const enabled = typeof selectedStep.args?.ga_campaign !== 'undefined';
  const panelBody = Hooks.applyFilters(
    'mailpoet.automation.send_email.google_analytics_panel',
    <PremiumModal
      onRequestClose={() =>
        dispatch(storeName).updateStepArgs(
          selectedStep.id,
          'ga_campaign',
          undefined,
        )
      }
    >
      {__(
        'Google Analytics tracking is not available in the free version of the MailPoet plugin.',
        'mailpoet',
      )}
    </PremiumModal>,
  );

  return (
    <PanelBody title="Google analytics" initialOpen={false}>
      <ToggleControl
        label="Enable custom GA tracking"
        checked={enabled}
        onChange={(value) =>
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'ga_campaign',
            value ? '' : undefined,
          )
        }
      />

      {enabled && panelBody}
    </PanelBody>
  );
}
