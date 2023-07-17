import { Button, SelectControl, TextControl } from '@wordpress/components';
import { dispatch, select, useSelect } from '@wordpress/data';
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

async function clearFilters(section: Section, onClick?: () => void) {
  await dispatch(storeName).updateSection({
    ...section,
    customQuery: {
      ...section.customQuery,
      filter: undefined,
      search: '',
    },
  });
  if (typeof onClick === 'undefined') {
    return;
  }
  onClick();
}

export function ClearAllFilters({
  section,
  onClick,
}: {
  section: Section;
  onClick?: () => void;
}): null | JSX.Element {
  if (!section.customQuery.filter && !section.customQuery.search) {
    return null;
  }
  return (
    <Button
      className="mailpoet-analytics-clear-filters"
      isTertiary
      onClick={() => void clearFilters(section, onClick)}
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

  return (
    <form
      className="mailpoet-analytics-filter"
      onSubmit={(e) => {
        e.preventDefault();

        dispatch(storeName).updateSection({
          ...section,
          customQuery: {
            ...section.customQuery,
            filter: section.currentView.filters,
            search: section.currentView.search,
          },
        });
      }}
    >
      <div className="mailpoet-analytics-filter-controls">
        <TextControl
          label={__('Search subscriber', 'mailpoet')}
          value={section.currentView.search}
          onChange={(search) =>
            void dispatch(storeName).updateCurrentView(section, {
              ...section.currentView,
              search,
            })
          }
        />
        <MultiSelect
          selected={section.currentView.filters.step}
          label={__('Select step', 'mailpoet')}
          allOption={__('All steps', 'mailpoet')}
          options={getStepOptions(automation)}
          onChange={(step) =>
            void dispatch(storeName).updateCurrentView(section, {
              ...section.currentView,
              filters: { ...section.currentView.filters, step },
            })
          }
        />
        <SelectControl
          label={__('Status', 'mailpoet')}
          options={getStatusOptions()}
          value={
            section.currentView.filters.status.length > 0
              ? section.currentView.filters.status[0]
              : ''
          }
          onChange={(status) =>
            void dispatch(storeName).updateCurrentView(section, {
              ...section.currentView,
              filters: { ...section.currentView.filters, status: [status] },
            })
          }
        />
      </div>
      <div>
        <ClearAllFilters
          section={section}
          onClick={() => {
            dispatch(storeName).updateCurrentView(section, {
              search: '',
              filters: {
                step: [],
                status: [],
              },
            });
          }}
        />
        <Button isPrimary type="submit">
          {__('Filter', 'mailpoet')}
        </Button>
      </div>
    </form>
  );
}
