import { InputControl, Stack, TextareaControl } from '@wordpress/ui';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
  store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { MAILPOET_EMAIL_PRESET_POST_TYPE } from '../constants';

// Keys must match EmailPresetPostType::META_* in PHP.
const META_DESCRIPTION = 'mailpoet_preset_description';
const META_SUBJECT = 'mailpoet_preset_subject';
const META_PREHEADER = 'mailpoet_preset_preheader';

type PresetMeta = Record<string, string>;

export function PresetSettingsPanel() {
  const postType = useSelect(
    (select) => select(editorStore).getCurrentPostType(),
    [],
  );
  const [title, setTitle] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_PRESET_POST_TYPE,
    'title',
  );
  const [meta, setMeta] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_PRESET_POST_TYPE,
    'meta',
  );
  const presetMeta = (meta ?? {}) as PresetMeta;
  const updateMeta = (key: string, value: string) =>
    setMeta({ ...presetMeta, [key]: value });

  // The panel would edit nothing while the template is open.
  if (postType !== MAILPOET_EMAIL_PRESET_POST_TYPE) {
    return null;
  }

  return (
    <PluginDocumentSettingPanel
      name="mailpoet-email-preset-settings"
      title={__('Preset settings', 'mailpoet')}
    >
      <Stack direction="column" gap="lg">
        <InputControl
          label={__('Name', 'mailpoet')}
          value={(title as string) ?? ''}
          onValueChange={setTitle}
        />
        <TextareaControl
          label={__('Description', 'mailpoet')}
          value={presetMeta[META_DESCRIPTION] ?? ''}
          rows={2}
          onValueChange={(value) => updateMeta(META_DESCRIPTION, value)}
        />
        <InputControl
          label={__('Subject', 'mailpoet')}
          value={presetMeta[META_SUBJECT] ?? ''}
          onValueChange={(value) => updateMeta(META_SUBJECT, value)}
        />
        <InputControl
          label={__('Preheader', 'mailpoet')}
          value={presetMeta[META_PREHEADER] ?? ''}
          onValueChange={(value) => updateMeta(META_PREHEADER, value)}
        />
      </Stack>
    </PluginDocumentSettingPanel>
  );
}
