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

export function getApiValidationMessage(error: unknown, fallback: string): string {
    if (!isApiValidationError(error)) {
        return fallback;
    }
    return error.response?.data?.message ?? fallback;
}
