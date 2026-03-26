import { PanelBody, PanelRow } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../store';
import { TrashButton } from '../../actions/trash-button';
import { locale } from '../../../../config';
import { Hooks } from '../../../../../hooks';
import { AutomationSettingElements } from '../../../../types/filters';
import { sendTelemetryEvent } from '../../../telemetry';

function AutomationSettings(): JSX.Element {
  const { automationId } = useSelect(
    (s) => ({ automationId: s(storeName).getAutomationData().id }),
    [],
  );
  const settings: AutomationSettingElements = Hooks.applyFilters(
    'mailpoet.automation.settings.render',
    {},
  );

  if (Object.keys(settings).length === 0) {
    return null;
  }

  return (
    <PanelBody
      title={__('Automation settings', 'mailpoet')}
      initialOpen
      onToggle={(isOpen) =>
        sendTelemetryEvent(isOpen ? 'section_expand' : 'section_collapse', {
          card_section: 'automation_settings',
          automation_id: automationId,
        })
      }
    >
      {Object.keys(settings).map((key) => (
        <PanelRow key={key}>{settings[key]}</PanelRow>
      ))}
    </PanelBody>
  );
}

export function AutomationSidebar(): JSX.Element {
  const { automationData } = useSelect(
    (select) => ({
      automationData: select(storeName).getAutomationData(),
    }),
    [],
  );

  const dateOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  };

  return (
    <>
      <PanelBody
        title={__('Automation details', 'mailpoet')}
        initialOpen
        onToggle={(isOpen) =>
          sendTelemetryEvent(isOpen ? 'section_expand' : 'section_collapse', {
            card_section: 'automation_details',
            automation_id: automationData.id,
          })
        }
      >
        <PanelRow>
          <strong>Date added</strong>{' '}
          {new Date(Date.parse(automationData.created_at)).toLocaleDateString(
            locale.toString(),
            dateOptions,
          )}
        </PanelRow>
        <PanelRow>
          <strong>Activated</strong>{' '}
          {automationData.status === 'active' &&
            new Date(Date.parse(automationData.updated_at)).toLocaleDateString(
              locale.toString(),
              dateOptions,
            )}
          {automationData.status !== 'active' &&
            automationData.activated_at &&
            new Date(
              Date.parse(automationData.activated_at),
            ).toLocaleDateString(locale.toString(), dateOptions)}
          {automationData.status !== 'active' &&
            !automationData.activated_at && (
              <span className="mailpoet-deactive">Not activated yet.</span>
            )}
        </PanelRow>
        <PanelRow>
          <strong>Author</strong> {automationData.author.name}
        </PanelRow>
        <PanelRow>
          <TrashButton />
        </PanelRow>
      </PanelBody>
      <AutomationSettings />
    </>
  );
}
