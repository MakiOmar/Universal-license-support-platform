import type { Customer } from "./types";

const TOKEN_KEY = "customer_token";
const CUSTOMER_KEY = "customer";

function isBrowser(): boolean {
  return typeof window !== "undefined" && typeof localStorage !== "undefined";
}

export function getToken(): string | null {
  if (!isBrowser()) return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function getCustomer(): Customer | null {
  if (!isBrowser()) return null;
  const raw = localStorage.getItem(CUSTOMER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as Customer;
  } catch {
    return null;
  }
}

export function setAuth(token: string, customer: Customer): void {
  if (!isBrowser()) return;
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(CUSTOMER_KEY, JSON.stringify(customer));
}

export function setCustomer(customer: Customer): void {
  if (!isBrowser()) return;
  localStorage.setItem(CUSTOMER_KEY, JSON.stringify(customer));
}

export function clearAuth(): void {
  if (!isBrowser()) return;
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(CUSTOMER_KEY);
}

export function isLoggedIn(): boolean {
  return Boolean(getToken());
}

export function customerDisplayName(customer: Customer | null): string {
  if (!customer) return "";
  const name = [customer.first_name, customer.last_name].filter(Boolean).join(" ").trim();
  return name || customer.email;
}
