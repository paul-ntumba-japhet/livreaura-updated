export type WritingLanguage = {
  code: string
  label: string
}

export const writingLanguages: readonly WritingLanguage[] = [
  { code: 'fr', label: 'Français' },
  { code: 'en', label: 'Anglais' },
  { code: 'lua', label: 'Tshiluba' },
  { code: 'ln', label: 'Lingala' },
  { code: 'sw', label: 'Swahili' },
  { code: 'kg', label: 'Kikongo' },
]

export const writingLanguageCodes: readonly string[] = writingLanguages.map(language => language.code)

export function isWritingLanguage(code: string): boolean {
  return writingLanguages.some(language => language.code === code)
}
