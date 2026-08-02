import { component$ } from "@builder.io/qwik";
import { Link, useLocation } from "@builder.io/qwik-city";
import { clearAuth, customerDisplayName, getCustomer, isLoggedIn } from "~/lib/auth";

export const SiteNav = component$(() => {
  const loc = useLocation();
  const loggedIn = isLoggedIn();
  const customer = getCustomer();

  return (
    <header class="site-header">
      <div class="container site-header-inner">
        <Link href="/" class="site-logo">
          ULSP Portal
        </Link>
        <nav class="site-nav">
          <Link href="/products">Products</Link>
          {loggedIn ? (
            <>
              <Link href="/app">Dashboard</Link>
              <Link href="/app/licenses">Licenses</Link>
              <Link href="/app/payments">Payments</Link>
              <Link href="/app/tickets">Tickets</Link>
              <Link href="/app/profile">Profile</Link>
              <span>{customerDisplayName(customer)}</span>
              <button
                type="button"
                class="btn btn-secondary"
                onClick$={async () => {
                  try {
                    const { apiPost } = await import("~/lib/api");
                    await apiPost("/auth/logout");
                  } catch {
                    // Always clear local session even if API logout fails.
                  }
                  clearAuth();
                  window.location.href = "/login";
                }}
              >
                Log out
              </button>
            </>
          ) : (
            <>
              <Link href="/login">Log in</Link>
              <Link href="/register" class="btn btn-primary">
                Register
              </Link>
            </>
          )}
          {loc.url.pathname.startsWith("/app") ? null : null}
        </nav>
      </div>
    </header>
  );
});

export const AppSubNav = component$(() => {
  return (
    <nav class="app-subnav">
      <Link href="/app">Dashboard</Link>
      <Link href="/app/licenses">Licenses</Link>
      <Link href="/app/payments">Payments</Link>
      <Link href="/app/tickets">Tickets</Link>
      <Link href="/app/profile">Profile</Link>
    </nav>
  );
});

export const LoadingState = component$(() => {
  return (
    <div class="state-box">
      <div class="spinner" aria-hidden="true"></div>
      <p>Loading…</p>
    </div>
  );
});

export const EmptyState = component$((props: { message: string }) => {
  return (
    <div class="card">
      <div class="state-box">{props.message}</div>
    </div>
  );
});

export const ErrorState = component$((props: { message: string }) => {
  return <div class="alert alert-error">{props.message}</div>;
});

export function formatDate(value?: string | null): string {
  if (!value) return "—";
  return new Date(value).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

export function statusBadgeClass(status: string): string {
  const normalized = status.toLowerCase();
  if (["active", "resolved", "open"].includes(normalized)) return "badge badge-green";
  if (["expired", "closed", "cancelled", "suspended"].includes(normalized)) return "badge badge-red";
  if (["pending", "in_progress", "waiting_customer", "medium", "high"].includes(normalized)) {
    return "badge badge-yellow";
  }
  if (["urgent"].includes(normalized)) return "badge badge-red";
  return "badge badge-gray";
}
