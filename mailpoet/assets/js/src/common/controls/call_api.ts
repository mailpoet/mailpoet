import MailPoet from 'mailpoet';
import { isErrorResponse } from '../../ajax';

export default async function callApi({ endpoint, action, data }) {
  try {
    const res = await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint,
      action,
      data,
    });
    return { success: true, res };
  } catch (res) {
    const error = isErrorResponse(res)
      ? res.errors.map((e) => e.message)
      : null;
    return { success: false, error, res };
  }
}
