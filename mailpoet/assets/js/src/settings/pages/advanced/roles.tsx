import ReactStringReplace from 'react-string-replace';

import { t } from 'common/functions';
import { useSelector } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Roles() {
  const isMembersPluginActive = useSelector('hasMembersPlugin')();
  return (
    <>
      <Label
        title={t('rolesTitle')}
        description={t('rolesDescription')}
        htmlFor=""
      />
      <Inputs>
        <p>
          {isMembersPluginActive ? (
            <a className="mailpoet-link" href="?page=roles">
              {t('manageUsingMembers')}
            </a>
          ) : (
            ReactStringReplace(
              t('installMembers'),
              /\[link\](.*?)\[\/link\]/,
              (text) => (
                <a
                  className="mailpoet-link"
                  key={text}
                  href="https://wordpress.org/plugins/members/"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {text}
                </a>
              ),
            )
          )}
        </p>
      </Inputs>
    </>
  );
}
