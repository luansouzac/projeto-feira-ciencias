export function useFileUtils() {
  const getFullStorageUrl = (filePath) => {
    if (!filePath) return null
    const baseUrl = import.meta.env.VITE_API_BASE_URL
    return `${baseUrl}/storage/${filePath}`
  }

  const isImage = (filePath) => {
    if (!filePath) return false
    return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(filePath)
  }

  return { getFullStorageUrl, isImage }
}