export const baseURL = __ENV.URL || 'http://localhost:9500'; // eslint-disable-line
export const headlessSet = ['true', '1'].includes(
  `${__ENV.HEADLESS || 'true'}`.toLowerCase(), // eslint-disable-line
);
export const scenario = __ENV.SCENARIO; // eslint-disable-line
export const timeoutSet = '2m';

export const adminUsername = 'admin';
export const adminPassword = __ENV.PW || 'password'; // eslint-disable-line
export const adminEmail = 'test@test.com';

export const firstName = 'John';
export const lastName = 'Doe';
export const defaultListName = 'Newsletter mailing list';

export const thinkTimeMin = '1';
export const thinkTimeMax = '3';

export const emailsPageTitle = 'Emails Page';
export const automationsPageTitle = 'Automations Page';
export const formsPageTitle = 'Forms Page';
export const subscribersPageTitle = 'Subscribers Page';
export const listsPageTitle = 'Lists Page';
export const settingsPageTitle = 'Settings Page';
