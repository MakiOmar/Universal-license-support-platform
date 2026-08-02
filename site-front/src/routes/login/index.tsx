import { $, component$, useSignal } from "@builder.io/qwik";
import { Link, useNavigate } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiPost } from "~/lib/api";
import { setAuth } from "~/lib/auth";
import type { AuthLoginResponse } from "~/lib/types";
import { ErrorState } from "~/components/site-chrome/site-chrome";

export default component$(() => {
  const nav = useNavigate();
  const email = useSignal("");
  const password = useSignal("");
  const loading = useSignal(false);
  const error = useSignal("");

  const handleSubmit = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiPost<AuthLoginResponse>(
        "/auth/login",
        {
          email: email.value,
          password: password.value,
        },
        false,
      );
      setAuth(response.token, response.customer);
      alert("Logged in successfully.");
      await nav("/app");
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Invalid email or password.";
    } finally {
      loading.value = false;
    }
  });

  return (
    <div class="auth-shell">
      <div class="auth-card card">
        <div class="card-body">
          <h1 style={{ textAlign: "center", marginTop: 0 }}>Sign in</h1>
          <p style={{ textAlign: "center", color: "var(--color-muted)" }}>
            Or <Link href="/register">create an account</Link>
          </p>

          <form
            preventdefault:submit
            onSubmit$={handleSubmit}
            style={{ marginTop: "1.5rem" }}
          >
            <div class="form-group">
              <label for="email">Email</label>
              <input
                id="email"
                class="form-control"
                type="email"
                autoComplete="email"
                required
                value={email.value}
                onInput$={(event) => {
                  email.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input
                id="password"
                class="form-control"
                type="password"
                autoComplete="current-password"
                required
                value={password.value}
                onInput$={(event) => {
                  password.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            <p style={{ fontSize: "0.875rem" }}>
              <Link href="/forgot-password">Forgot your password?</Link>
            </p>
            {error.value ? <ErrorState message={error.value} /> : null}
            <button type="submit" class="btn btn-primary" style={{ width: "100%" }} disabled={loading.value}>
              {loading.value ? "Signing in…" : "Sign in"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
});

export const head: DocumentHead = {
  title: "Log in — ULSP",
};
