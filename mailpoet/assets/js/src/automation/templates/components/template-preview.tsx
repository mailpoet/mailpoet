import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, warning } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { createStore, storeName } from '../../editor/store';
import { AutomationTemplate } from '../config';
import { Automation } from '../../editor/components/automation';
import { initializeIntegrations } from '../../editor/integrations';

const initializeHooks = () => {
  Hooks.addFilter(
    'mailpoet.automation.step.more-controls',
    'mailpoet',
    () => () => {},
    20,
  );

  Hooks.addFilter(
    'mailpoet.automation.render_step_separator',
    'mailpoet',
    () =>
      function Separator() {
        return <div className="mailpoet-automation-editor-separator" />;
      },
  );
};

const cleanupHooks = () => {
  Hooks.removeFilter('mailpoet.automation.step.more-controls', 'mailpoet');
  Hooks.removeFilter('mailpoet.automation.render_step_separator', 'mailpoet');
};

type Props = {
  template: AutomationTemplate;
};

export function TemplatePreview({ template }: Props): JSX.Element {
  const [state, setState] = useState<'loading' | 'loaded' | 'error'>('loading');

  useEffect(() => {
    const controller = new AbortController();
    const loadTemplate = async () => {
      setState('loading');

      initializeHooks();
      createStore();
      initializeIntegrations();

      try {
        const data = await apiFetch<{ data: { automation: unknown } }>({
          path: `/automation-templates/${template.slug}`,
          method: 'GET',
          signal: controller.signal,
        });
        dispatch(storeName).updateAutomation(data.data.automation);
        setState('loaded');
      } catch (error) {
        if (!controller.signal?.aborted) {
          setState('error');
        }
      }
    };

    void loadTemplate();

    return () => {
      controller.abort();
      cleanupHooks();
    };
  }, [template.slug]);

  if (state === 'error') {
    return (
      <div className="mailpoet-automation-template-detail-preview-error">
        <div>
          <Icon icon={warning} size={20} />
        </div>
        <div>{__('There was an error loading the preview.', 'mailpoet')}</div>
        <div>{__('Please, try again.')}</div>
      </div>
    );
  }

  if (state === 'loading') {
    return (
      <div className="mailpoet-automation-template-detail-preview-spinner">
        <Spinner />
      </div>
    );
  }

  return <Automation context="view" showStatistics={false} />;
}
