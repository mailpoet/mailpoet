import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, warning } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { createStore, storeName } from '../../editor/store';
import { AutomationTemplate } from '../config';
import { Automation } from '../../editor/components/automation';
import { initializeIntegrations } from '../../editor/integrations';
import { Step as StepData } from '../../editor/components/automation/types';

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
      function SeperatorWrapper(previousStepData: StepData, index: number) {
        const stepType = useSelect(
          (select) => select(storeName).getStepType(previousStepData.key),
          [],
        );

        const BranchBadge =
          previousStepData.next_steps.length > 1 && stepType?.branchBadge;

        return (
          <>
            {previousStepData.next_steps.length > 1 && (
              <div
                className={
                  index < previousStepData.next_steps.length / 2
                    ? 'mailpoet-automation-editor-separator-curve-leaf-left'
                    : 'mailpoet-automation-editor-separator-curve-leaf-right'
                }
              />
            )}
            {BranchBadge && (
              <div className="mailpoet-automation-editor-branch-badge">
                <BranchBadge step={previousStepData} index={index} />
              </div>
            )}
            <div className="mailpoet-automation-editor-separator" />
          </>
        );
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
        void dispatch(storeName).updateAutomation(data.data.automation);
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
