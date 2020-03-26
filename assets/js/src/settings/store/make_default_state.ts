import { State } from './types';
import normalizeSettings from './normalize_settings';

export default function makeDefaultState(window: any): State {
  return {
    save: {
      inProgress: false,
      error: null,
    },
    flags: {
      error: false,
      woocommerce: !!window.mailpoet_woocommerce_active,
      newUser: !!window.mailpoet_is_new_user,
    },
    data: normalizeSettings(window.mailpoet_settings),
    segments: window.mailpoet_segments,
    pages: window.mailpoet_pages,
  };
}
