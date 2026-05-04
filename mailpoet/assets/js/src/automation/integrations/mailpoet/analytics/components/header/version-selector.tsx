import { useEffect } from 'react';
import { SelectControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { storeName } from '../../store';
import { AutomationVersion } from '../../store/types';

const ALL_VERSIONS = 'all';

function formatLabel(version: AutomationVersion): string {
  const date = dateI18n('M j, Y, g:i a', version.created_at, true);
  return version.is_current
    ? sprintf(
        // translators: %s is a localised date/time, e.g. "Mar 5, 2026, 2:15 pm"
        __('Current version (saved %s)', 'mailpoet'),
        date,
      )
    : sprintf(
        // translators: %s is a localised date/time
        __('Version saved %s', 'mailpoet'),
        date,
      );
}

export function VersionSelector(): JSX.Element | null {
  const { versions, selectedVersionId } = useSelect(
    (s) => ({
      versions: s(storeName).getVersions(),
      selectedVersionId: s(storeName).getSelectedVersionId(),
    }),
    [],
  );

  useEffect(() => {
    void dispatch(storeName).loadVersions();
  }, []);

  if (versions.length <= 1) {
    return null;
  }

  const value =
    selectedVersionId === undefined ? ALL_VERSIONS : String(selectedVersionId);

  const options = [
    { label: __('All versions', 'mailpoet'), value: ALL_VERSIONS },
    ...versions.map((version) => ({
      label: formatLabel(version),
      value: String(version.id),
    })),
  ];

  return (
    <div className="mailpoet-analytics-version-selector">
      <SelectControl
        label={__('Version', 'mailpoet')}
        value={value}
        options={options}
        onChange={(next) => {
          void dispatch(storeName).setSelectedVersionId(
            next === ALL_VERSIONS ? undefined : Number(next),
          );
        }}
        __nextHasNoMarginBottom
      />
    </div>
  );
}
