import { z } from 'zod'

export const requiredString = () => z.string().trim().min(1, { error: 'validation.required' })

export const emailField = () => requiredString().pipe(z.email({ error: 'validation.email' }))
