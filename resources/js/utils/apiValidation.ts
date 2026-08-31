import axios, { type AxiosError } from 'axios';

type ApiValidationResponse = {
    message?: string;
    errors?: Record<string, string[]>;
};

export function isApiValidationError(error: unknown): error is AxiosError<ApiValidationResponse> {
    return axios.isAxiosError<ApiValidationResponse>(error) && error.response?.status === 422;
}

export function getApiValidationFieldErrors(error: unknown): Record<string, string> {
    if (!isApiValidationError(error)) {
        return {};
    }

    const fieldErrors: Record<string, string> = {};
    for (const [field, messages] of Object.entries(error.response?.data?.errors ?? {})) {
        if (Array.isArray(messages) && messages[0]) {
            fieldErrors[field] = messages[0];
        }
    }
    return fieldErrors;
}

/*
 * Like getApiValidationFieldErrors, but with Laravel's dot-notation array
 * indices (entries.0.unit_price) converted to the bracket notation of
 * TanStack Form field names (entries[0].unit_price), so that the errors
 * attach to the mounted fields.
 */
export function getApiValidationFormFieldErrors(error: unknown): Record<string, string> {
    return Object.fromEntries(
        Object.entries(getApiValidationFieldErrors(error)).map(([field, message]) => [
            field.replace(/\.(\d+)(?=\.|$)/g, '[$1]'),
            message,
        ])
    );
}

export function getApiValidationMessage(error: unknown, fallback: string): string {
    if (!isApiValidationError(error)) {
        return fallback;
    }
    return error.response?.data?.message ?? fallback;
}
