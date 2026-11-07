export type RegistrationCountry = {
  code: string
  label: string
}

export const registrationCountries: readonly RegistrationCountry[] = [
  { code: 'DZ', label: 'Algérie' },
  { code: 'BE', label: 'Belgique' },
  { code: 'BJ', label: 'Bénin' },
  { code: 'BF', label: 'Burkina Faso' },
  { code: 'BI', label: 'Burundi' },
  { code: 'CM', label: 'Cameroun' },
  { code: 'CA', label: 'Canada' },
  { code: 'KM', label: 'Comores' },
  { code: 'CG', label: 'Congo' },
  { code: 'CI', label: 'Côte d’Ivoire' },
  { code: 'DJ', label: 'Djibouti' },
  { code: 'FR', label: 'France' },
  { code: 'GA', label: 'Gabon' },
  { code: 'GN', label: 'Guinée' },
  { code: 'GQ', label: 'Guinée équatoriale' },
  { code: 'MG', label: 'Madagascar' },
  { code: 'ML', label: 'Mali' },
  { code: 'MA', label: 'Maroc' },
  { code: 'MU', label: 'Maurice' },
  { code: 'MR', label: 'Mauritanie' },
  { code: 'NE', label: 'Niger' },
  { code: 'CF', label: 'République centrafricaine' },
  { code: 'CD', label: 'République démocratique du Congo' },
  { code: 'RW', label: 'Rwanda' },
  { code: 'SN', label: 'Sénégal' },
  { code: 'SC', label: 'Seychelles' },
  { code: 'TD', label: 'Tchad' },
  { code: 'TG', label: 'Togo' },
  { code: 'TN', label: 'Tunisie' },
]

export function isRegistrationCountry(code: string): boolean {
  return registrationCountries.some(country => country.code === code)
}
