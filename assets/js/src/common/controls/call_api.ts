import MailPoet from 'mailpoet';

export default async function ({ endpoint, action, data }) {
  try {
    const res = await MailPoet.Ajax.post({
      api_version: (window as any).mailpoet_api_version,
      endpoint,
      action,
      data,
    });
    return { success: true, res };
  } catch (res) {
    const error = res.errors.map((e) => e.message);
    return { success: false, error, res };
  }
}
