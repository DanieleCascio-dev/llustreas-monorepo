/**
 * Inject Cloudinary on-the-fly transformations into an upload URL.
 * Non-Cloudinary URLs are returned unchanged.
 */
export function cloudinaryThumb(url, width) {
  if (!url || !url.includes('/upload/')) return url
  return url.replace('/upload/', `/upload/c_fill,w_${width},q_auto,f_auto/`)
}
