import type { ComputedRef, InjectionKey } from 'vue'

export type RadioGroupContext = {
  name: ComputedRef<string>
  selected: ComputedRef<string>
  disabled: ComputedRef<boolean>
  setValue: (value: string) => void
}

export const radioGroupKey: InjectionKey<RadioGroupContext> = Symbol('RadioGroup')
