import { clearAuth, getToken } from "./auth";

export class ApiError extends Error {
  status: number;
  payload: unknown;

  constructor(message: string, status: number, payload?: unknown) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}

/** Normalize API base so callers always hit /api/v1, even if env ends with /api. */
export function normalizeApiBase(base: string): string {
  const trimmed = base.replace(/\/+$/, "");

  if (trimmed.endsWith("/api/v1")) {
    return trimmed;
  }

  if (trimmed.endsWith("/api")) {
    return `${trimmed}/v1`;
  }

  return trimmed;
}

export function getApiBase(): string {
  const fromEnv = import.meta.env.PUBLIC_API_BASE;
  return normalizeApiBase(fromEnv || "http://127.0.0.1:8000/api/v1");
}

function buildUrl(path: string): string {
  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  const base = getApiBase();
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${base}${normalizedPath}`;
}

function extractErrorMessage(payload: unknown, fallback: string): string {
  if (!payload || typeof payload !== "object") {
    return fallback;
  }

  const record = payload as Record<string, unknown>;

  if (typeof record.message === "string" && record.message.trim()) {
    return record.message;
  }

  const nested = record.error;
  if (nested && typeof nested === "object" && typeof (nested as Record<string, unknown>).message === "string") {
    return (nested as Record<string, string>).message;
  }

  if (record.errors && typeof record.errors === "object") {
    const messages = Object.values(record.errors as Record<string, unknown>)
      .flatMap((value) => (Array.isArray(value) ? value : [value]))
      .filter((value): value is string => typeof value === "string");
    if (messages.length > 0) {
      return messages.join(", ");
    }
  }

  return fallback;
}

async function parseJsonSafe(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) return null;
  try {
    return JSON.parse(text);
  } catch {
    return { message: text };
  }
}

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
  auth = true,
): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (!(options.body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (auth) {
    const token = getToken();
    if (token) {
      headers.set("Authorization", `Bearer ${token}`);
    }
  }

  const response = await fetch(buildUrl(path), {
    ...options,
    headers,
  });

  const payload = await parseJsonSafe(response);

  if (!response.ok) {
    if (response.status === 401 && typeof window !== "undefined") {
      clearAuth();
      const pathname = window.location.pathname;
      if (!pathname.startsWith("/login") && !pathname.startsWith("/register")) {
        window.location.href = "/login";
      }
    }

    throw new ApiError(
      extractErrorMessage(payload, response.statusText || "Request failed"),
      response.status,
      payload,
    );
  }

  return payload as T;
}

export function apiGet<T>(path: string, auth = true): Promise<T> {
  return apiRequest<T>(path, { method: "GET" }, auth);
}

export function apiPost<T>(path: string, body?: unknown, auth = true): Promise<T> {
  return apiRequest<T>(
    path,
    {
      method: "POST",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    },
    auth,
  );
}

export function apiPut<T>(path: string, body?: unknown, auth = true): Promise<T> {
  return apiRequest<T>(
    path,
    {
      method: "PUT",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    },
    auth,
  );
}

export function unwrapData<T>(payload: T | { data: T }): T {
  if (payload && typeof payload === "object" && "data" in payload) {
    return (payload as { data: T }).data;
  }
  return payload as T;
}

export function unwrapList<T>(payload: T[] | PaginatedLike<T> | null | undefined): T[] {
  if (!payload) return [];
  if (Array.isArray(payload)) return payload;
  if (typeof payload === "object" && "data" in payload && Array.isArray(payload.data)) {
    return payload.data;
  }
  return [];
}

interface PaginatedLike<T> {
  data: T[];
}
