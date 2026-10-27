import { request } from '@canvas/api/client';
import type { CredentialDefinition } from '@canvas/types/graph';

export type CredentialItem = {
    id: number;
    name: string;
    connected_email?: string;
    is_connected?: boolean;
};

export type { CredentialDefinition };

export async function fetchCredentials(type: string): Promise<{
    items: CredentialItem[];
    definitions: CredentialDefinition[];
}> {
    const response = await request<{
        data: CredentialItem[];
        meta: { types: CredentialDefinition[] };
    }>(`/app/api/credentials?type=${encodeURIComponent(type)}`);

    return {
        items: response.data,
        definitions: response.meta.types ?? [],
    };
}
