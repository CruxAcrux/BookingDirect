import type { StandardSchemaV1 } from '@standard-schema/spec'
import type { TypedSchema, TypedSchemaError } from 'vee-validate'

/**
 * Lets VeeValidate use any Standard Schema validator (Zod 4 here).
 * The official @vee-validate/zod adapter only supports Zod 3.
 */
export function toTypedSchema<TSchema extends StandardSchemaV1>(
  schema: TSchema,
): TypedSchema<StandardSchemaV1.InferInput<TSchema>, StandardSchemaV1.InferOutput<TSchema>> {
  return {
    __type: 'VVTypedSchema',
    async parse(values) {
      const result = await schema['~standard'].validate(values)

      if (!result.issues) return { value: result.value, errors: [] }

      const byPath = new Map<string, string[]>()
      for (const issue of result.issues) {
        const path = (issue.path ?? [])
          .map((segment) => String(typeof segment === 'object' ? segment.key : segment))
          .join('.')
        byPath.set(path, [...(byPath.get(path) ?? []), issue.message])
      }

      const errors: TypedSchemaError[] = [...byPath].map(([path, messages]) => ({
        path,
        errors: messages,
      }))
      return { errors }
    },
  }
}
