export type ShareLink = {
  name: 'facebook' | 'x' | 'whatsapp' | 'email';
  url: string;
};

export function getShareLinks(shareUrl: string, subject = ''): ShareLink[] {
  const encodedUrl = encodeURIComponent(shareUrl);
  const encodedSubject = encodeURIComponent(subject);
  return [
    {
      name: 'facebook',
      url: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    },
    {
      name: 'x',
      url: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedSubject}`,
    },
    {
      name: 'whatsapp',
      url: `https://wa.me/?text=${encodedSubject}%20${encodedUrl}`,
    },
    {
      name: 'email',
      url: `mailto:?subject=${encodedSubject}&body=${encodedUrl}`,
    },
  ];
}
