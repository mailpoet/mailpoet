import { HTMLAttributes } from 'react';
import { speak } from '@wordpress/a11y';
import { forwardRef, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// See: https://github.com/WordPress/gutenberg/blob/628ae68152f572d0b395bb15c0f71b8821e7f130/packages/block-editor/src/components/inserter-listbox/group.js

type Props = HTMLAttributes<HTMLDivElement>;

export const InserterListboxGroup = forwardRef<HTMLDivElement, Props>(
  (props, ref): JSX.Element => {
    const [shouldSpeak, setShouldSpeak] = useState(false);

    useEffect(() => {
      if (shouldSpeak) {
        speak(__('Use left and right arrow keys to move through blocks'));
      }
    }, [shouldSpeak]);

    return (
      <div
        ref={ref}
        role="listbox"
        aria-orientation="horizontal"
        onFocus={() => {
          setShouldSpeak(true);
        }}
        onBlur={(event) => {
          const focusingOutsideGroup = !event.currentTarget.contains(
            event.relatedTarget,
          );
          if (focusingOutsideGroup) {
            setShouldSpeak(false);
          }
        }}
        {...props}
      />
    );
  },
);
