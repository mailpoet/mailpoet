import React from 'react';
import { useSelector } from 'settings/store/hooks';
import { onChange, t } from 'common/functions';

type Props = {
  id?: string
  value: string
  automationId?: string
  linkAutomationId?: string
  setValue: (x: string) => any
  preview: 'manage' | 'unsubscribe' | 'confirm' | 'confirm_unsubscribe'
}

export default (props: Props) => {
  const pages = useSelector('getPages')();
  let selectedPage = pages.find((x) => x.id === parseInt(props.value, 10));
  if (!selectedPage) selectedPage = pages[0];
  return (
    <>
      <select
        id={props.id}
        data-automation-id={props.automationId}
        value={selectedPage.id}
        onChange={onChange(props.setValue)}
      >
        {pages.map((page) => (
          <option key={page.id} value={page.id}>
            {`${page.title}`}
          </option>
        ))}
      </select>
      {' '}
      <a
        target="_blank"
        title={t('previewPage')}
        rel="noopener noreferrer"
        href={selectedPage.url[props.preview]}
        data-automation-id={props.linkAutomationId}
      >
        {t('preview')}
      </a>
    </>
  );
};
