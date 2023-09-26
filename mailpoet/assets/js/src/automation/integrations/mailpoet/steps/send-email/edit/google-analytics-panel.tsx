import { useState } from 'react';
import { ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PremiumModal } from 'common/premium_modal';
import { Hooks } from 'wp-js-hooks';
import { storeName } from '../../../../../editor/store';
import { GoogleAnalyticsPanelBodyType } from '../../../types/filters';
import { PanelBody } from '../../../../../editor/components/panel/panel-body';

export function GoogleAnalyticsPanel(): JSX.Element {
  const { selectedStep, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      )?.fields?.ga_campaign,
    }),
    [],
  );

  const { updateStepArgs } = useDispatch(storeName);

  const hasValue = typeof selectedStep.args?.ga_campaign !== 'undefined';
  const [enabled, setEnabled] = useState(hasValue);

  const panelBody: GoogleAnalyticsPanelBodyType = Hooks.applyFilters(
    'mailpoet.automation.send_email.google_analytics_panel',
    <PremiumModal
      onRequestClose={() => {
        setEnabled(false);
        updateStepArgs(selectedStep.id, 'ga_campaign', undefined);
      }}
    >
      {__(
        'Google Analytics tracking is not available in the free version of the MailPoet plugin.',
        'mailpoet',
      )}
    </PremiumModal>,
  );

  return (
    <PanelBody
      title={__('Google Analytics', 'mailpoet')}
      initialOpen={false}
      hasErrors={!!errors}
    >
      <ToggleControl
        label={__('Enable custom GA tracking', 'mailpoet')}
        checked={enabled}
        onChange={(value) => {
          setEnabled(value);
          if (!value) {
            updateStepArgs(selectedStep.id, 'ga_campaign', undefined);
          }
        }}
      />

      {enabled && panelBody}
    </PanelBody>
  );
}
