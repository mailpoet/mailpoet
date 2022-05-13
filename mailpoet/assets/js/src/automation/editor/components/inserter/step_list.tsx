import { ComponentProps, ReactNode } from 'react';
import { Item } from './item';
import { InserterListItem } from './step_list_item';
import { InserterListboxGroup } from '../inserter-listbox/listbox_group';
import { InserterListboxRow } from '../inserter-listbox/listbox_row';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/block-editor/src/components/block-types-list/index.js

const chunk = <T extends unknown[]>(array: T, size: number): T[] => {
  const chunks = [] as T[];
  for (let i = 0, j = array.length; i < j; i += size) {
    chunks.push(array.slice(i, i + size) as T);
  }
  return chunks;
};

const getBlockMenuDefaultClassName = (blockName: string): string =>
  `editor-block-list-item-${blockName.replace(/\//, '-')}`;

type InserterListItemProps = ComponentProps<typeof InserterListItem>;

type Props = {
  label: string;
  items: Item[];
  onSelect: InserterListItemProps['onSelect'];
  onHover: InserterListItemProps['onHover'];
  isDraggable?: boolean;
  children?: ReactNode;
};

export function StepList({
  items,
  onSelect,
  onHover,
  label,
  isDraggable = true,
  children = undefined,
}: Props): JSX.Element {
  return (
    <InserterListboxGroup
      className="block-editor-block-types-list"
      aria-label={label}
    >
      {chunk(items, 3).map((row, i) => (
        // eslint-disable-next-line react/no-array-index-key -- looks safe, comes from the Gutenberg code
        <InserterListboxRow key={i}>
          {row.map((item, j) => (
            <InserterListItem
              key={item.id}
              item={item}
              className={getBlockMenuDefaultClassName(item.id)}
              onSelect={onSelect}
              onHover={onHover}
              isDraggable={isDraggable}
              isFirst={i === 0 && j === 0}
            />
          ))}
        </InserterListboxRow>
      ))}
      {children}
    </InserterListboxGroup>
  );
}
