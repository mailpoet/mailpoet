export type EditorChoice = 'new' | 'classic';

export type CreatedAutomationEmail = {
  emailId: number;
  emailWpPostId?: number;
};

type NewsletterCreateResponse = {
  data?: {
    id?: unknown;
    wp_post_id?: unknown;
  };
};

export type SavedAutomationResult = {
  saved?: boolean;
  automation?: {
    steps?: Record<
      string,
      {
        args?: Record<string, unknown>;
      }
    >;
  };
};

export const editorChoiceButtonLabels: Record<EditorChoice, string> = {
  new: 'design_with_new_editor',
  classic: 'design_with_classic_editor',
};

export const parsePositiveInteger = (value: unknown): number | undefined => {
  if (typeof value !== 'number' && typeof value !== 'string') {
    return undefined;
  }

  const parsedValue = Number(value);
  if (!Number.isInteger(parsedValue) || parsedValue <= 0) {
    return undefined;
  }

  return parsedValue;
};

export const getCreatedAutomationEmail = (
  response: NewsletterCreateResponse,
  editorChoice: EditorChoice,
): CreatedAutomationEmail | undefined => {
  const emailId = parsePositiveInteger(response?.data?.id);
  if (!emailId) {
    return undefined;
  }

  if (editorChoice === 'classic') {
    return { emailId };
  }

  const emailWpPostId = parsePositiveInteger(response?.data?.wp_post_id);
  if (!emailWpPostId) {
    return undefined;
  }

  return { emailId, emailWpPostId };
};

export const isCreatedEmailPersisted = (
  saveResult: SavedAutomationResult | undefined,
  stepId: string,
  createdEmail: CreatedAutomationEmail,
  editorChoice: EditorChoice,
): boolean => {
  if (saveResult?.saved !== true) {
    return false;
  }

  const stepArgs = saveResult.automation?.steps?.[stepId]?.args;
  if (!stepArgs) {
    return false;
  }

  if (parsePositiveInteger(stepArgs.email_id) !== createdEmail.emailId) {
    return false;
  }

  if (editorChoice === 'classic') {
    return !Object.prototype.hasOwnProperty.call(stepArgs, 'email_wp_post_id');
  }

  return (
    parsePositiveInteger(stepArgs.email_wp_post_id) ===
    createdEmail.emailWpPostId
  );
};
