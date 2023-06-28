import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useSelect } from '@wordpress/data';
import { Filter } from './filter';
import { MailPoet } from '../../../../../../mailpoet';
import { storeName as editorStoreName } from '../../../../../editor/store/constants';

export function Header(): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));
  return (
    <header className="mailpoet-analytics-header">
      <Filter />
      <Button
        href={addQueryArgs(MailPoet.urls.automationEditor, {
          id: automation.id,
        })}
        isPrimary
      >
        {__('Edit automation', 'mailpoet')}
      </Button>
    </header>
  );
}
