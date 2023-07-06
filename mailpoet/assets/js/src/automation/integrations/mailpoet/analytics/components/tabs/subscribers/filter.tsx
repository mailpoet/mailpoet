import { useState } from 'react';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { dispatch, select, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { statusMap } from './cells/status';
import { storeName as editorStoreName } from '../../../../../../editor/store';
import { Automation } from '../../../../../../editor/components/automation/types';
import { storeName, SubscriberSection } from '../../../store';

const sortByLabelCallback = (a: { label: string }, b: { label: string }) => {
  if (a.label > b.label) {
    return 1;
  }
  return b.label > a.label ? -1 : 0;
};

function getStatusOptions() {
  const statusOptions: { label: string; value: string }[] = Object.keys(
    statusMap,
  )
    .map((status: string) => ({
      value: status,
      label: statusMap[status] as string,
    }))
    .sort(sortByLabelCallback);
  return [{ value: '', label: __('All', 'mailpoet') }].concat(statusOptions);
}

function getStepOptions(automation: Automation) {
  const steps = Object.values(automation.steps)
    .map((step) => {
      if (step.id === 'root') {
        return null;
      }
      const label = select(editorStoreName).getRegistryStep(step.key)?.name;
      return {
        value: step.id,
        label,
      };
    })
    .filter((value) => value !== null)
    .sort(sortByLabelCallback);

  return [{ label: __('All', 'mailpoet'), value: '' }].concat(steps);
}

export function Filter(): JSX.Element | null {
  const { automation, section } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
    section: s(storeName).getSection('subscribers') as SubscriberSection,
  }));

  const [step, setStep] = useState(section.customQuery.filter?.step);
  const [search, setSearch] = useState(section.customQuery.filter?.search);
  const [status, setStatus] = useState(section.customQuery.filter?.status);

  return (
    <div className="mailpoet-analytics-filter">
      <div className="mailpoet-analytics-filter-controls">
        <TextControl
          label={__('Search subscriber', 'mailpoet')}
          value={search as string}
          onChange={(value) => setSearch(value)}
        />
        <SelectControl
          label={__('Select step', 'mailpoet')}
          options={getStepOptions(automation)}
          value={step as string}
          onChange={(value) => {
            setStep(value);
          }}
        />
        <SelectControl
          label={__('Status', 'mailpoet')}
          options={getStatusOptions()}
          value={status as string}
          onChange={(value) => {
            setStatus(value);
          }}
        />
      </div>
      <Button
        isPrimary
        onClick={() => {
          dispatch(storeName).updateSection({
            ...section,
            customQuery: {
              ...section.customQuery,
              filter: {
                step,
                status,
              },
              search,
            },
          });
        }}
      >
        {__('Filter', 'mailpoet')}
      </Button>
    </div>
  );
}
