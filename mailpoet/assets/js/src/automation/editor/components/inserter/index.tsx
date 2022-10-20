import { forwardRef, Fragment, useCallback, useMemo } from 'react';
import { SearchControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef, useImperativeHandle, useState } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { blockDefault, Icon } from '@wordpress/icons';
import { Group } from './group';
import { Item } from './item';
import { StepInfoPanel } from './step_info_panel';
import { StepList } from './step_list';
import { InserterListbox } from '../inserter-listbox';
import { storeName } from '../../store';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter/menu.js

const filterItems = (value: string, item: Item[]): Item[] =>
  item.filter((step) =>
    step.title.toLowerCase().includes(value.trim().toLowerCase()),
  );

type Props = {
  onInsert?: (item: Item) => void;
};

export const Inserter = forwardRef(({ onInsert }: Props, ref): JSX.Element => {
  const [filterValue, setFilterValue] = useState('');
  const [hoveredItem, setHoveredItem] = useState(null);

  const { steps, type } = useSelect(
    (select) => ({
      steps: select(storeName).getSteps(),
      type: select(storeName).getInserterPopover().type,
    }),
    [],
  );

  const groups: Group[] = useMemo(
    () =>
      type === 'triggers'
        ? [
            {
              type: 'triggers',
              title: undefined,
              // translators: Label for a list of automation steps of type trigger
              label: _x('Triggers', 'automation steps', 'mailpoet'),
              items: steps.filter(({ group }) => group === 'triggers'),
            },
          ]
        : [
            {
              type: 'actions',
              // translators: Label for a list of automation steps of type action
              title: _x('Actions', 'automation steps', 'mailpoet'),
              // translators: Label for a list of automation steps of type action
              label: _x('Actions', 'automation steps', 'mailpoet'),
              items: steps.filter(({ group }) => group === 'actions'),
            },
            {
              type: 'logical',
              // translators: Label for a list of logical automation steps (if/else, etc.)
              title: _x('Logical', 'automation steps', 'mailpoet'),
              // translators: Label for a list of logical automation steps (if/else, etc.)
              label: _x('Logical', 'automation steps', 'mailpoet'),
              items: steps.filter(({ group }) => group === 'logical'),
            },
          ],
    [steps, type],
  );

  const onHover = useCallback(
    (item) => {
      setHoveredItem(item);
    },
    [setHoveredItem],
  );

  const searchRef = useRef<HTMLInputElement>();
  useImperativeHandle(ref, () => ({
    focusSearch: () => {
      searchRef.current?.focus();
    },
  }));

  const filteredGroups = useMemo(
    () =>
      groups.map((group) => ({
        ...group,
        items: filterItems(filterValue, group.items),
      })),
    [filterValue, groups],
  );

  return (
    <div className="block-editor-inserter__menu">
      <div className="block-editor-inserter__main-area">
        <div className="block-editor-inserter__content">
          <SearchControl
            className="block-editor-inserter__search"
            onChange={(value: string) => {
              if (hoveredItem) setHoveredItem(null);
              setFilterValue(value);
            }}
            value={filterValue}
            label={__('Search for automation steps', 'mailpoet')}
            placeholder={__('Search', 'mailpoet')}
            ref={searchRef}
          />

          <div className="block-editor-inserter__block-list">
            <InserterListbox>
              {filteredGroups.map(
                (group) =>
                  group.items.length > 0 && (
                    <Fragment key={group.type}>
                      {group.title && (
                        <div className="block-editor-inserter__panel-header">
                          <h2 className="block-editor-inserter__panel-title">
                            <div>{group.title}</div>
                          </h2>
                        </div>
                      )}
                      <div className="block-editor-inserter__panel-content">
                        <StepList
                          items={group.items}
                          onHover={onHover}
                          onSelect={(item: Item) => onInsert(item)}
                          label={group.label}
                        />
                      </div>
                    </Fragment>
                  ),
              )}

              {filteredGroups.reduce(
                (sum, { items }) => sum + items.length,
                0,
              ) === 0 && (
                <div className="block-editor-inserter__no-results">
                  <Icon
                    className="block-editor-inserter__no-results-icon"
                    icon={blockDefault}
                  />
                  <p>{__('No results found.', 'mailpoet')}</p>
                </div>
              )}
            </InserterListbox>
          </div>
        </div>
      </div>
      {hoveredItem && <StepInfoPanel item={hoveredItem} />}
    </div>
  );
});
