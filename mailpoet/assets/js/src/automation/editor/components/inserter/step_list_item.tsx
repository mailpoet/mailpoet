import classnames from 'classnames';
import { ComponentProps } from 'react';
import { useRef, memo } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import { Item } from './item';
import { InserterListboxItem } from '../inserter-listbox/listbox_item';
import { StepIcon } from '../step-icon';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-list-item/index.js

const isAppleOS = (): boolean => {
  const { platform } = window.navigator;
  return (
    platform.indexOf('Mac') !== -1 || ['iPad', 'iPhone'].includes(platform)
  );
};

type ListboxItemProps = ComponentProps<typeof InserterListboxItem>;

type Props = Omit<ListboxItemProps, 'onSelect' | 'onHover'> & {
  item: Item;
  onSelect: (item: Item, isModifierKey: boolean) => void;
  onHover: (item: Item) => void;
  isDraggable: boolean;
};

export const InserterListItem = memo(
  ({
    className,
    isFirst,
    item,
    onSelect,
    onHover,
    isDraggable,
    ...props
  }: Props): JSX.Element => {
    const isDragging = useRef(false);

    return (
      <div className="block-editor-block-types-list__list-item">
        <InserterListboxItem
          isFirst={isFirst}
          className={classnames(
            'block-editor-block-types-list__item',
            className,
          )}
          disabled={item.isDisabled}
          onClick={(event) => {
            event.preventDefault();
            onSelect(item, isAppleOS() ? event.metaKey : event.ctrlKey);
            onHover(null);
          }}
          onKeyDown={(event) => {
            const { keyCode } = event;
            if (keyCode === ENTER) {
              event.preventDefault();
              onSelect(item, isAppleOS() ? event.metaKey : event.ctrlKey);
              onHover(null);
            }
          }}
          onFocus={() => {
            if (isDragging.current) {
              return;
            }
            onHover(item);
          }}
          onMouseEnter={() => {
            if (isDragging.current) {
              return;
            }
            onHover(item);
          }}
          onMouseLeave={() => onHover(null)}
          onBlur={() => onHover(null)}
          {...props}
        >
          <span className="block-editor-block-types-list__item-icon">
            <StepIcon icon={item.icon} />
          </span>
          <span className="block-editor-block-types-list__item-title">
            {item.title}
          </span>
        </InserterListboxItem>
      </div>
    );
  },
);
