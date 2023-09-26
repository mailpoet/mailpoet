import { Panel, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { isEqual } from 'lodash';
import { storeName } from '../../store';
import { TokenField } from '../../../common/form/token-field/token-field';
import { MailPoet } from '../../../mailpoet';

export function TagsPanel({ onToggle, isOpened }) {
  const settings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );

  const { changeFormSettings } = useDispatch(storeName);

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
