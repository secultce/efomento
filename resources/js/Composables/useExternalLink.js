export function useExternalLink() {
  const openExternal = (url, options = {}) => {
    console.log('Abrindo link externo:', url)
    if (!url) return

    const {
      target = '_blank',
      features = 'noopener,noreferrer',
      fallback = true,
    } = options

    // SSR / segurança
    if (typeof window === 'undefined') return

    const newWindow = window.open(url, target, features)
    console.log('Tentando abrir link externo:', url, { newWindow })
    // 🔥 fallback caso popup seja bloqueado
    if (!newWindow && fallback) {
      const link = document.createElement('a')
      link.href = url
      link.target = target
      link.rel = 'noopener noreferrer'
      link.click()
    }
  }

  return {
    openExternal,
  }
}