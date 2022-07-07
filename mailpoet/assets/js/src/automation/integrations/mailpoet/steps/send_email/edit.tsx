import { PanelBody, TextareaControl, TextControl } from '@wordpress/components';
import { edit, Icon } from '@wordpress/icons';
import {
  PlainBodyTitle,
  TitleActionButton,
} from '../../../../editor/components/panel';

export function Edit(): JSX.Element {
  return (
    <PanelBody opened>
      <PlainBodyTitle title="Email">
        <TitleActionButton>
          <Icon icon={edit} size={16} />
        </TitleActionButton>
      </PlainBodyTitle>
      <TextControl
        label="“From” name"
        placeholder="John Doe"
        value=""
        onChange={() => {}}
      />
      <TextControl
        label="“From” email address"
        placeholder="you@domain.com"
        value=""
        onChange={() => {}}
      />
      <TextareaControl
        label="Subject"
        placeholder="Type in subject…"
        value=""
        onChange={() => {}}
      />
      <TextareaControl
        label="Preheader"
        placeholder="Type in preheader…"
        value=""
        onChange={() => {}}
      />
    </PanelBody>
  );
}
