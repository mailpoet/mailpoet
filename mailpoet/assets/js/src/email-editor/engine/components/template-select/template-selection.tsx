import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { storeName } from 'email-editor/engine/store';
import { SelectTemplateModal } from './select-modal';

export function TemplateSelection() {
  const [templateSelected, setTemplateSelected] = useState(false);
  const { emailContentIsEmpty } = useSelect(
    (select) => ({
      emailContentIsEmpty: select(storeName).hasEmptyContent(),
    }),
    [],
  );

  if (!emailContentIsEmpty || templateSelected) {
    return null;
  }

  return (
    <SelectTemplateModal onSelectCallback={() => setTemplateSelected(true)} />
  );
}
