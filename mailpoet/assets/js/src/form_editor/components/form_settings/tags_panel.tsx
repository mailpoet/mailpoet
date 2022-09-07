import { Panel, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { isEqual } from 'lodash';
import { TokenField } from '../../../common/form/tokenField/tokenField';
import { MailPoet } from '../../../mailpoet';

export function TagsPanel({ onToggle, isOpened }) {
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const onSegmentsChange = (e) => {
    if (isEqual(settings.tags, e.value)) {
      return;
    }

    void changeFormSettings({
      ...settings,
      tags: e.value,
    });
  };

  const tags = MailPoet.tags.map((tag) => tag.name);
  return (
    <Panel>
      <PanelBody title="Tags" opened={isOpened} onToggle={onToggle}>
        <TokenField
          label={MailPoet.I18n.t('addNewTag')}
          onChange={onSegmentsChange}
          suggestedValues={tags}
          selectedValues={settings.tags}
        />
      </PanelBody>
    </Panel>
  );
}
