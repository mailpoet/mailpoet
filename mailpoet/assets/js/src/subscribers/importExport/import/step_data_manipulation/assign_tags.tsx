import { MailPoet } from 'mailpoet';
import { useCallback } from 'react';
import { TokenField } from '../../../../common/form/tokenField/tokenField';

interface Props {
  selectedTags: string[];
  setSelectedTags: (value) => void;
}

export function AssignTags({
  selectedTags,
  setSelectedTags,
}: Props): JSX.Element {
  const handleChange = useCallback(
    ({ value }): void => {
      setSelectedTags(value);
    },
    [setSelectedTags],
  );

  const tags = MailPoet.tags.map((tag) => tag.name);
  return (
    <>
      <div className="mailpoet-settings-label">
        {MailPoet.I18n.t('assignTagsLabel')}
        <p className="description">
          {MailPoet.I18n.t('assignTagsDescription')}
        </p>
      </div>
      <div className="mailpoet-settings-inputs mailpoet-import-tags">
        <TokenField
          name="tags"
          onChange={handleChange}
          suggestedValues={tags}
          selectedValues={selectedTags}
          placeholder={MailPoet.I18n.t('addNewTag')}
        />
      </div>
    </>
  );
}
