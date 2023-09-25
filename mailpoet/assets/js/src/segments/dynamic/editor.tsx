import { useEffect } from 'react';
import { Button, Flex, FlexBlock } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';

import { useRouteMatch } from 'react-router-dom';

import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { Form } from './form';
import { storeName } from './store';

export function Editor(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();

  const { pageLoaded, pageUnloaded } = useDispatch(storeName);
  const previousPage: string = useSelect((select) =>
    select(storeName).getPreviousPage(),
  );
  const returnPage: string = previousPage || '/';

  useEffect(() => {
    void pageLoaded(match.params.id);

    return () => {
      void pageUnloaded();
    };
  }, [match.params.id, pageLoaded, pageUnloaded]);

  const isNewSegment =
    match.params.id === undefined || Number.isNaN(Number(match.params.id));

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBeamer />
      <HideScreenOptions />

      <Flex
        className="mailpoet-heading"
        direction={['column', 'row'] as any} // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports string[] and this is needed to make the component responsive
        gap="16px"
      >
        <FlexBlock>
          <h1 className="wp-heading-inline">
            <Button
              id="mailpoet-segments-back-button"
              icon={chevronLeft}
              href={`#${returnPage}`}
              label={__('Return to previous page', 'mailpoet')}
            />
            {match.params.id
              ? __('Edit segment', 'mailpoet')
              : __('New segment', 'mailpoet')}
          </h1>
        </FlexBlock>
      </Flex>

      <Form isNewSegment={isNewSegment} />
    </div>
  );
}
Editor.displayName = 'SegmentEditor';
