import { State, Settings } from './types';

export default function makeDefaultState(window: any): State {
  return {
    save: {
      inProgress: false,
      error: null,
    },
    flags: {
      woocommerce: !!window.mailpoet_woocommerce_active,
      newUser: !!window.mailpoet_is_new_user,
    },
    data: window.mailpoet_settings,
    segments: window.mailpoet_segments,
  };
}
