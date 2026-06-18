import type { Segment } from './api';

export type PickerKind = 'segment' | 'tag';

export type PickerConfig = {
  kind: PickerKind;
  fieldId: string;
  endpoint: 'segments' | 'tags';
  filter?: (segment: Segment) => boolean;
};

// Select2 shows its first option as soon as it renders, but notifies a
// controlled consumer only through its change event, which never fires on
// mount. Returning that first option lets a consumer seed its state to match
// what is already on screen.
export function getInitialPickerValue(config: PickerConfig): number {
  if (config.endpoint === 'segments') {
    const segments = window.mailpoet_segments ?? [];
    const selectable = config.filter
      ? segments.filter(config.filter)
      : segments;
    return selectable.length ? Number(selectable[0].id) : 0;
  }
  const tags = window.mailpoet_tags ?? [];
  return tags.length ? Number(tags[0].id) : 0;
}
