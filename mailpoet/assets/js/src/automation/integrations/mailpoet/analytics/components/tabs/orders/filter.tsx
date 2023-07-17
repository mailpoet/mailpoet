import { Button } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { OrderSection, storeName } from '../../../store';
import { MultiSelect } from '../../multiselect';
import { ClearAllFilters } from '../subscribers/filter';

export function Filter(): JSX.Element {
  const { section } = useSelect((s) => ({
    section: s(storeName).getSection('orders') as OrderSection,
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
            filter: {
              emails: section.currentView.filters.emails,
            },
          },
        });
      }}
    >
      <div className="mailpoet-analytics-filter-controls">
        <MultiSelect
          selected={section.currentView.filters.emails}
          label={__('Select Email', 'mailpoet')}
          allOption={__('All emails', 'mailpoet')}
          options={section.data?.emails}
          onChange={(emails) => {
            dispatch(storeName).updateCurrentView(section, {
              filters: { emails },
            });
          }}
        />
      </div>
      <div>
        <ClearAllFilters
          section={section}
          onClick={() => {
            dispatch(storeName).updateCurrentView(section, {
              filters: { emails: [] },
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
