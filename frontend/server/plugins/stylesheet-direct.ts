function withDirectStylesheetQuery(value: string): string {
  return value.replace(
    /(<link\b[^>]*\brel=["']stylesheet["'][^>]*\bhref=["'])([^"']+\.css)(\?[^"']*)?(["'])/gi,
    (match, prefix: string, file: string, query: string | undefined, quote: string) => {
      if (query?.includes('direct')) {
        return match
      }
      const nextQuery = query ? `${query}&direct` : '?direct'
      return `${prefix}${file}${nextQuery}${quote}`
    },
  )
}

export default defineNitroPlugin((nitroApp) => {
  nitroApp.hooks.hook('render:html', (html) => {
    html.head = html.head.map(item => (
      typeof item === 'string' ? withDirectStylesheetQuery(item) : item
    ))
  })
})
