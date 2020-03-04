export type Settings = {
  sender: {
    name: string
    address: string
  }
  reply_to: {
    name: string
    address: string
  }
  subscribe: {
    on_comment: {
      enabled: boolean
      label: string
      segments: string[]
    }
    on_register: {
      enabled: boolean
      label: string
      segments: string[]
    }
  }
  subscription: {
    pages: {
      manage: string
      unsubscribe: string
      confirmation: string
      captcha: string
    }
    segments: string[]
  }
  stats_notifications: {
    enabled: boolean
    automated: boolean
    address: string
  }
  subscriber_email_notification: {
    enabled: boolean
    address: string
  }
  // ...
}

export type State = {
  data: Settings
  save: {
    inProgress: boolean
    error: any
  }
}

export type Action =
  | { type: 'SET_SETTING'; value: any; path: string[] }
  | { type: 'SAVE_STARTED' }
  | { type: 'SAVE_DONE' }
  | { type: 'SAVE_FAILED'; error: any }
