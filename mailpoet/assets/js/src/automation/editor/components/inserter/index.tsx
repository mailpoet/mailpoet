import { forwardRef, Fragment, useCallback, useMemo } from 'react';
import { SearchControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef, useImperativeHandle, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { blockDefault, Icon } from '@wordpress/icons';
import { Group } from './group';
import { Item } from './item';
import { StepInfoPanel } from './step_info_panel';
import { StepList } from './step_list';
import { InserterListbox } from '../inserter-listbox';
import { store } from '../../store';

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
      steps: select(store).getSteps(),
      type: select(store).getInserterPopover().type,
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
              label: __('Triggers', 'mailpoet'),
              items: steps.filter(({ group }) => group === 'triggers'),
            },
          ]
        : [
            {
              type: 'actions',
              title: __('Actions', 'mailpoet'),
              label: __('Actions', 'mailpoet'),
              items: steps.filter(({ group }) => group === 'actions'),
            },
            {
              type: 'logical',
              title: __('Logical', 'mailpoet'),
              label: __('Logical', 'mailpoet'),
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
            label={__('Search for blocks and patterns')}
            placeholder={__('Search')}
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
                  <p>{__('No results found.')}</p>
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
