interface ApiResponse<T = unknown> {
    data?: T
    message?: string
    error?: string
    [key: string]: unknown
}

class ApiClient {
    private baseUrl = '/api'

    private async request<T>(
        method: string,
        path: string,
        body?: Record<string, unknown>,
    ): Promise<ApiResponse<T>> {
        const headers: Record<string, string> = {
            Accept: 'application/json',
        }

        const token = localStorage.getItem('auth_token')
        if (token) {
            headers['Authorization'] = `Bearer ${token}`
        }

        if (body !== undefined) {
            headers['Content-Type'] = 'application/json'
        }

        const response = await fetch(`${this.baseUrl}${path}`, {
            method,
            headers,
            body: body !== undefined ? JSON.stringify(body) : undefined,
        })

        const json = (await response.json()) as ApiResponse<T>

        if (!response.ok) {
            const err = new Error(
                (json.message as string) ||
                    (json.error as string) ||
                    'Request failed',
            ) as Error & { status: number; data: ApiResponse<T> }
            err.status = response.status
            err.data = json
            throw err
        }

        return json
    }

    get<T>(path: string) {
        return this.request<T>('GET', path)
    }

    post<T>(path: string, body?: Record<string, unknown>) {
        return this.request<T>('POST', path, body)
    }

    put<T>(path: string, body?: Record<string, unknown>) {
        return this.request<T>('PUT', path, body)
    }

    delete<T>(path: string) {
        return this.request<T>('DELETE', path)
    }
}

export const api = new ApiClient()
