import MailPoet from 'mailpoet';

export async function SEND_DATA_TO_API({ data }) {
  try {
    await MailPoet.Ajax.post({
      api_version: (window as any).mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    });
    return false;
  } catch (res) {
    return res.errors.map((e) => e.message);
  }
}

export function TRACK_SETTINGS_SAVED({ data }) {
  // ...
}
