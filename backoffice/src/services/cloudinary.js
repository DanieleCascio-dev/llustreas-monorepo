import { cloudinaryBrowser } from './api'

export async function uploadImage(file, folder = 'illustreas') {
  const { data: sig } = await cloudinaryBrowser.sign(folder)

  const formData = new FormData()
  formData.append('file', file)
  formData.append('api_key', sig.api_key)
  formData.append('timestamp', sig.timestamp)
  formData.append('signature', sig.signature)
  formData.append('folder', sig.folder)
  formData.append('asset_folder', sig.asset_folder)

  const url = `https://api.cloudinary.com/v1_1/${sig.cloud_name}/image/upload`
  const res = await fetch(url, { method: 'POST', body: formData })

  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.error?.message || 'Upload failed')
  }

  const data = await res.json()
  return data.secure_url
}

export function getOptimizedUrl(url, { width, quality = 'auto', format = 'auto' } = {}) {
  if (!url || !url.includes('cloudinary')) return url
  const parts = url.split('/upload/')
  if (parts.length !== 2) return url
  const transforms = [`f_${format}`, `q_${quality}`]
  if (width) transforms.push(`w_${width}`)
  return `${parts[0]}/upload/${transforms.join(',')}/${parts[1]}`
}
