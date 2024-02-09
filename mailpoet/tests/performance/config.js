export const baseURL = __ENV.URL || 'http://localhost:9500'; // eslint-disable-line
export const headlessSet = ['true', '1'].includes(
  `${__ENV.HEADLESS || 'true'}`.toLowerCase(), // eslint-disable-line
);
export const scenario = __ENV.SCENARIO; // eslint-disable-line
export const projectName = __ENV.K6_PROJECT_NAME; // eslint-disable-line
export const k6CloudID = __ENV.K6_CLOUD_ID; // eslint-disable-line
export const fullPageSet = 'true';
export const screenshotPath = 'tests/performance/_screenshots/';

export const fromName = 'MP Perf Testing';
export const adminUsername = __ENV.US || 'admin'; // eslint-disable-line
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
export const segmentsPageTitle = 'Segments Page';
export const listsPageTitle = 'Lists Page';
export const settingsPageTitle = 'Settings Page';
