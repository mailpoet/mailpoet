import { Card, CardBody, CardMedia, Panel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { styles } from '@wordpress/icons';
import { storeName, stylesSidebarId } from '../../store';
import { StylesPreview } from './styles-preview';

type Props = ComponentProps<typeof ComplementaryArea>;

export function StylesSidebar(props: Props): JSX.Element {
  return (
    <ComplementaryArea
      identifier={stylesSidebarId}
      className="edit-post-styles"
      header={__('Styles', 'mailpoet')}
      icon={styles}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      {...props}
    >
      <Panel>
        <Card
          size="small"
          className="edit-site-global-styles-screen-root"
          variant="primary"
        >
          <CardBody>
            <Card>
              <CardMedia>
                <StylesPreview />
              </CardMedia>
            </Card>
          </CardBody>
        </Card>
      </Panel>
    </ComplementaryArea>
  );
}
