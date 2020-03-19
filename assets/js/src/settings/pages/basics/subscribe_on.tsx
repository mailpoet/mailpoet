import React from 'react';
import { t, onChange, onToggle } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, SegmentsSelect } from 'settings/components';

type Props = {
  title: string
  description: string
  event: 'on_comment' | 'on_register'
}

export default function SubscribeOn({ title, description, event }: Props) {
  const [enabled, setEnabled] = useSetting('subscribe', event, 'enabled');
  const [label, setLabel] = useSetting('subscribe', event, 'label');
  const [segments, setSegments] = useSetting('subscribe', event, 'segments');
  return (
    <>
      <Label
        title={title}
        description={description}
        htmlFor={`subscribe-${event}-enabled`}
      />
      <Inputs>
        <input
          type="checkbox"
          id={`subscribe-${event}-enabled`}
          data-automation-id={`subscribe-${event}-checkbox`}
          checked={enabled === '1'}
          onChange={onToggle(setEnabled)}
        />
        {enabled === '1' && (
          <>
            <br />
            <input
              type="text"
              className="regular-text"
              value={label || t`yesAddMe`}
              onChange={onChange(setLabel)}
            />
            <br />
            <label htmlFor={`subscribe-${event}-segments`}>{t`usersWillBeSubscribedTo`}</label>
            <br />
            <div data-automation-id={`subscribe-${event}-segments-selection`}>
              <SegmentsSelect id={`subscribe-${event}-segments`} placeholder={t`chooseList`} value={segments} setValue={setSegments} />
            </div>
          </>
        )}
      </Inputs>
    </>
  );
}
