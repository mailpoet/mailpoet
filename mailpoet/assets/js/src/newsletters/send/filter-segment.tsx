import { NewsLetter } from 'common/newsletter';
import { Field } from 'form/types';
import {
  ChangeEvent,
  useCallback,
  useEffect,
  useState,
  useContext,
} from 'react';
import { __ } from '@wordpress/i18n';
import { Selection } from 'form/fields/selection';
import { Toggle } from 'common';
import { premiumValidAndActive } from 'common/premium-modal';
import { Tooltip } from 'common/tooltip/tooltip';
import { Icon, help } from '@wordpress/icons';
import ReactStringReplace from 'react-string-replace';
import { SendContext } from '../send_context';

type FilterSegmentProps = {
  item?: NewsLetter;
  onValueChange: (targetWrap: {
    target: {
      name: string;
      value: unknown;
    };
  }) => void;
  field: Field;
};

export function FilterSegment({
  item,
  onValueChange,
  field,
}: FilterSegmentProps) {
  const currentFilterSegmentId = item?.options.filterSegmentId;

  const [isFilterSegmentEnabled, setIsFilterSegmentEnabled] = useState<boolean>(
    premiumValidAndActive && !!currentFilterSegmentId,
  );

  const updateFilterSegmentId = useCallback(
    (id: string): void => {
      const currentOptions = item?.options ?? {};

      onValueChange({
        target: {
          name: 'options',
          value: { ...currentOptions, filterSegmentId: id },
        },
      });
    },
    [item, onValueChange],
  );

  const context = useContext(SendContext);

  useEffect(() => {
    if (!premiumValidAndActive && currentFilterSegmentId !== '') {
      updateFilterSegmentId('');
    }
  }, [updateFilterSegmentId, currentFilterSegmentId]);

  const handleToggle = useCallback(
    (checked: boolean) => {
      onValueChange({
        target: {
          name: field.name,
          value: checked,
        },
      });
      if (!checked) {
        updateFilterSegmentId('');
      }
      setIsFilterSegmentEnabled(checked);
    },
    [field, onValueChange, updateFilterSegmentId],
  );

  let filterSegmentSelect;

  if (isFilterSegmentEnabled) {
    const filterSegmentField = {
      name: 'filter-segment',
      type: 'selection',
      placeholder: __('Choose', 'mailpoet'),
      id: 'mailpoetFilterSegment',
      api_version: window.mailpoet_api_version,
      endpoint: 'segments',
      multiple: false,
      forceSelect2: true,
      selected: (newsletter: NewsLetter) => newsletter.options.filterSegmentId,
      filter: function filter(segment: {
        deleted_at: string;
        type: string;
      }): boolean {
        return !segment.deleted_at && segment.type === 'dynamic';
      },
      getLabel: function getLabel(segment: { name: string }): string {
        return segment.name;
      },
      getCount: function getCount(segment: { subscribers: string }): string {
        return parseInt(segment.subscribers, 10).toLocaleString();
      },
      validation: {
        'data-parsley-required': true,
        'data-parsley-required-message': __(
          'Please select a filter segment',
          'mailpoet',
        ),
      },
    };

    filterSegmentSelect = (
      <Selection
        item={item}
        field={filterSegmentField}
        onValueChange={(event: ChangeEvent<HTMLInputElement>) => {
          updateFilterSegmentId(event.target.value);
        }}
      />
    );
  }
  return (
    <>
      <Toggle
        checked={isFilterSegmentEnabled}
        disabled={field.disabled}
        name="isFilterSegmentEnabled"
        onCheck={handleToggle}
        automationId="filter-segment-toggle"
      />
      <span className="mailpoet-form-toggle-text">
        {__('Filter by segment', 'mailpoet')}
        <Icon
          data-tip
          data-for="filter-segment-tooltip"
          className="filter-segment-tooltip"
          icon={help}
        />
      </span>
      <Tooltip place="right" multiline id="filter-segment-tooltip">
        <div>
          {__(
            `Subscribers selected in 'Send to' will only receive an email if they also belong to this segment.`,
            'mailpoet',
          )}
        </div>
      </Tooltip>
      <div className="mailpoet-gap" />
      {filterSegmentSelect}
      {isFilterSegmentEnabled && (
        <p>
          {ReactStringReplace(
            __(
              "Can't find the segment you're looking for? [link]Create new[/link]",
              'mailpoet',
            ),
            /\[link\](.*?)\[\/link\]/g,
            (match, i) => (
              <a
                className="mailpoet-link"
                key={i}
                rel="noopener noreferrer"
                onClick={(event) => {
                  event.preventDefault();
                  context.saveDraftNewsletter(() => {
                    window.location.href = `admin.php?page=mailpoet-segments#/new-segment?newsletterId=${item.id}`;
                  });
                }}
                href={`admin.php?page=mailpoet-segments#/new-segment?newsletterId=${item.id}`}
              >
                {match}
              </a>
            ),
          )}
        </p>
      )}
    </>
  );
}
