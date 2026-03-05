/**
 * WordPress REST API client with nonce authentication.
 */

function getConfig() {
  return window.csmsConfig ?? {
    apiBase: '/wp-json/csms/v1/',
    nonce: '',
    isLoggedIn: false,
    loginUrl: '/wp-login.php',
    user: null,
    pluginUrl: '',
    version: '1.0.0',
  }
}

export async function apiFetch<T>(
  path: string,
  options?: RequestInit,
): Promise<T> {
  const config = getConfig()
  const url = `${config.apiBase}${path}`

  const res = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce,
      ...options?.headers,
    },
  })

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }))
    throw new ApiError(res.status, error.message ?? error.error ?? 'Request failed')
  }

  // Handle 204 No Content.
  if (res.status === 204) {
    return undefined as T
  }

  return res.json()
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export function isLoggedIn(): boolean {
  return getConfig().isLoggedIn
}

export function getUser() {
  return getConfig().user
}

export function getLoginUrl(): string {
  return getConfig().loginUrl
}
