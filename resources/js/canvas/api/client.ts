export class ApiError extends Error {
    constructor(
        public readonly status: number,
        public readonly errors: Record<string, string[]> = {},
        message?: string,
    ) {
        super(message ?? `Request failed with status ${status}`);
        this.name = 'ApiError';
    }

    firstError(): string | null {
        for (const messages of Object.values(this.errors)) {
            if (messages.length > 0) {
                return messages[0];
            }
        }
        return null;
    }

    errorCount(): number {
        return Object.keys(this.errors).length;
    }
}

const csrfToken = (): string =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

export async function request<T>(url: string, init: RequestInit = {}): Promise<T> {
    const response = await fetch(url, {
        ...init,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...init.headers,
        },
    });

    if (!response.ok) {
        let errors: Record<string, string[]> = {};
        let message: string | undefined;

        try {
            const body = await response.json();
            errors = body.errors ?? {};
            message = body.message;
        } catch {
        }

        throw new ApiError(response.status, errors, message);
    }

    const text = await response.text();

    return (text ? JSON.parse(text) : undefined) as T;
}
