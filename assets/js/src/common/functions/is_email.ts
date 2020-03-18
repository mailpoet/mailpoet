export default (value: string): boolean => (window as any).mailpoet_email_regex.test(value);
