export function useEnums() {
  const getLabel = (options, value, fallback = '-') => {
    if (!value) return fallback

    const found = options?.find(opt => opt.value === value)
    return found?.label ?? value
  }

  return {
    getLabel
  }
}