export type NewsletterType = {
  id: string
  total_sent: number
  subject: string
  queue: object
  clicked_links: {cnt: string, url: string}[]
  statistics: {
    clicked: number
    opened: number
    unsubscribed: number
    revenue: {
      value: number
      formatted: string
      count: number
    }
  }
}
