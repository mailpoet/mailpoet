import { useState } from 'react';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { dispatch, select, useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { statusMap } from './cells/status';
import { storeName as editorStoreName } from '../../../../../../editor/store';
import { Automation } from '../../../../../../editor/components/automation/types';
import { Section, storeName, SubscriberSection } from '../../../store';
import { MultiSelect, MultiSelectOption } from '../../multiselect';

const sortByLabelCallback = (a: { label: string }, b: { label: string }) => {
  if (a.label > b.label) {
    return 1;
  }
  return b.label > a.label ? -1 : 0;
};
const sortByNameCallback = (a: { name: string }, b: { name: string }) => {
  if (a.name > b.name) {
    return 1;
  }
  return b.name > a.name ? -1 : 0;
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
      if (step.type !== 'action') {
        return null;
      }
      const name = select(editorStoreName).getRegistryStep(step.key)?.name;
      return {
        id: step.id,
        key: step.key,
        name,
        children: [],
      };
    })
    .filter((value) => value !== null)
    .sort(sortByNameCallback);

  const stepTypes: Record<string, MultiSelectOption> = {};
  for (let i = 0; i < steps.length; i += 1) {
    const s = steps[i];
    const key = s.key;
    if (stepTypes[key] === undefined) {
      stepTypes[key] = {
        id: '',
        name: s.name,
        children: [],
      };
    }
    stepTypes[key].id =
      stepTypes[key].id.length > 0 ? `${stepTypes[key].id},${s.id}` : s.id;
    stepTypes[key].children.push(s);
  }
  return Object.values(stepTypes).map((stepType) => {
    const children = stepType.children.map((step, i) => ({
      ...step,
      name: sprintf(__('%d. "%s" step', 'mailpoet'), i + 1, step.name),
    }));
    return {
      ...stepType,
      children: stepType.children.length === 1 ? [] : children,
    };
  });
}

function ClearAllFilters({
  section,
  onClick,
}: {
  section: Section;
  onClick: () => void;
}): null | JSX.Element {
  const { updateSection } = useDispatch(storeName);
  const clearFilters = () => {
    updateSection({
      ...section,
      customQuery: {
        ...section.customQuery,
        filter: undefined,
        search: '',
      },
    });
    onClick();
  };

  if (!section.customQuery.filter && !section.customQuery.search) {
    return null;
  }
  return (
    <Button
      className="mailpoet-analytics-clear-filters"
      isTertiary
      onClick={clearFilters}
    >
      {__('Clear all filters', 'mailpoet')}
    </Button>
  );
}

export function Filter(): JSX.Element | null {
  const { automation, section } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
    section: s(storeName).getSection('subscribers') as SubscriberSection,
  }));

  const [step, setStep] = useState<string[]>(section.customQuery.filter?.step);
  const [search, setSearch] = useState(section.customQuery.search);
  const [status, setStatus] = useState(section.customQuery.filter?.status);

  return (
    <form
      className="mailpoet-analytics-filter"
      onSubmit={(e) => {
        e.preventDefault();

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
      <div className="mailpoet-analytics-filter-controls">
        <TextControl
          label={__('Search subscriber', 'mailpoet')}
          value={search}
          onChange={(value) => setSearch(value)}
        />
        <MultiSelect
          selected={step}
          label={__('Select step', 'mailpoet')}
          allOption={__('All steps', 'mailpoet')}
          options={getStepOptions(automation)}
          onChange={(value) => {
            setStep(value);
          }}
        />
        <SelectControl
          label={__('Status', 'mailpoet')}
          options={getStatusOptions()}
          value={status && status.length > 0 ? status[0] : ''}
          onChange={(value) => {
            setStatus([value]);
          }}
        />
      </div>
      <div>
        <ClearAllFilters
          section={section}
          onClick={() => {
            setStep([]);
            setSearch('');
            setStatus([]);
          }}
        />
        <Button isPrimary type="submit">
          {__('Filter', 'mailpoet')}
        </Button>
      </div>
    </form>
  );
}
