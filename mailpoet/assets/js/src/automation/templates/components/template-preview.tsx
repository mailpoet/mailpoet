import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
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
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    const controller = new AbortController();
    const loadTemplate = async () => {
      setIsLoaded(false);

      initializeHooks();
      createStore();
      initializeIntegrations();

      const data = await apiFetch<{ data: { automation: unknown } }>({
        path: `/automation-templates/${template.slug}`,
        method: 'GET',
        signal: controller.signal,
      });
      dispatch(storeName).updateAutomation(data.data.automation);
      setIsLoaded(true);
    };
    void loadTemplate();

    return () => {
      controller.abort();
      cleanupHooks();
    };
  }, [template.slug]);

  return isLoaded ? (
    <Automation context="view" showStatistics={false} />
  ) : (
    <div className="mailpoet-automation-template-detail-preview-spinner">
      <Spinner />
    </div>
  );
}
