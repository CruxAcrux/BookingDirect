import { z } from 'zod'

export const requiredString = () => z.string().trim().min(1, { error: 'validation.required' })

export const optionalString = () =>
  z
    .string()
    .trim()
    .transform((value) => (value === '' ? null : value))

export const emailField = () => requiredString().pipe(z.email({ error: 'validation.email' }))

export const optionalEmail = () =>
  optionalString().pipe(z.email({ error: 'validation.email' }).nullable())

const inRange = (min: number, max: number) => (value: number | null) =>
  value === null || (value >= min && value <= max)

/** A whole number typed into a text field. */
export const integerField = (min: number, max: number) =>
  requiredString()
    .regex(/^\d+$/, { error: 'validation.integer' })
    .transform(Number)
    .refine(inRange(min, max), { error: 'validation.outOfRange' })

/** Like {@link integerField}, but an empty field means null. */
export const optionalIntegerField = (min: number, max: number) =>
  z
    .string()
    .trim()
    .refine((value) => value === '' || /^\d+$/.test(value), { error: 'validation.integer' })
    .transform((value) => (value === '' ? null : Number(value)))
    .refine(inRange(min, max), { error: 'validation.outOfRange' })

/** Kronor typed by the user ("349" or "349,50"), converted to öre. */
export const kronorField = () =>
  requiredString()
    .regex(/^\d+([.,]\d{1,2})?$/, { error: 'validation.price' })
    .transform((value) => Math.round(Number(value.replace(',', '.')) * 100))

/** Öre to the text shown in a kronor field. */
export function oreToKronorInput(ore: number): string {
  return ore % 100 === 0 ? String(ore / 100) : (ore / 100).toFixed(2).replace('.', ',')
}
