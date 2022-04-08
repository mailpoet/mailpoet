import { useEffect } from 'react';
import { t, onChange } from 'common/functions';
import Checkbox from 'common/form/checkbox/checkbox';
import Input from 'common/form/input/input';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, SegmentsSelect } from 'settings/components';

type Props = {
  title: string;
  description: string;
  event: 'on_comment' | 'on_register';
};

export default function SubscribeOn({ title, description, event }: Props) {
  const [enabled, setEnabled] = useSetting('subscribe', event, 'enabled');
  const [label, setLabel] = useSetting('subscribe', event, 'label');
  const [segments, setSegments] = useSetting('subscribe', event, 'segments');
  useEffect(() => {
    if (label === '') setLabel(t('yesAddMe'));
  }, [label, setLabel]);

  return (
    <>
      <Label
        title={title}
        description={description}
        htmlFor={`subscribe-${event}-enabled`}
      />
      <Inputs>
        <Checkbox
          id={`subscribe-${event}-enabled`}
          automationId={`subscribe-${event}-checkbox`}
          checked={enabled === '1'}
          onCheck={(isChecked) => setEnabled(isChecked ? '1' : '0')}
        />
        {enabled === '1' && (
          <>
            <br />
            <Input
              dimension="small"
              type="text"
              value={label}
              onChange={onChange(setLabel)}
            />
            <label
              className="mailpoet-settings-inputs-row"
              htmlFor={`subscribe-${event}-segments`}
            >
              {t('usersWillBeSubscribedTo')}
            </label>
            <div data-automation-id={`subscribe-${event}-segments-selection`}>
              <SegmentsSelect
                id={`subscribe-${event}-segments`}
                placeholder={t('chooseList')}
                value={segments}
                setValue={setSegments}
              />
            </div>
          </>
        )}
      </Inputs>
    </>
  );
}
