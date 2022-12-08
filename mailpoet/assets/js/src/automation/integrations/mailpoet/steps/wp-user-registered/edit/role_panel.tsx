import { PanelBody } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ReactStringReplace from 'react-string-replace';
import { storeName } from '../../../../../editor/store';
import {
  PlainBodyTitle,
  FormTokenField,
} from '../../../../../editor/components';
import { userRoles } from './role';

function SettingsInfoText(): JSX.Element {
  return (
    <p>
      {ReactStringReplace(
        __(
          '[link]Subscribe in registration form[/link] setting must be enabled.',
          'mailpoet',
        ),
        /\[link\](.*?)\[\/link\]/g,
        (match) => (
          <a href="admin.php?page=mailpoet-settings#/basics" target="_blank">
            {match}
          </a>
        ),
      )}
    </p>
  );
}

export function RolePanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const rawSelected = selectedStep.args?.roles
    ? (selectedStep.args.roles as string[])
    : [];
  const selected = userRoles.filter((role): boolean =>
    rawSelected.includes(role.id as string),
  );

  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />
      <SettingsInfoText />
      <FormTokenField
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The following error seems to be a mismatch. It claims the 'label' prop does not exist, but it does.
        label={__('When WordPress user role is:', 'mailpoet')}
        value={selected}
        suggestions={userRoles}
        placeholder={__('Any user role', 'mailpoet')}
        onChange={(items) => {
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'roles',
            items.map((item) => item.id),
          );
        }}
      />
    </PanelBody>
  );
}
