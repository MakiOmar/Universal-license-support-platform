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
  const firstName = useSignal("");
  const lastName = useSignal("");
  const password = useSignal("");
  const loading = useSignal(false);
  const error = useSignal("");

  const handleSubmit = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiPost<AuthLoginResponse>(
        "/auth/register",
        {
          email: email.value,
          password: password.value,
          first_name: firstName.value,
          last_name: lastName.value,
        },
        false,
      );
      setAuth(response.token, response.customer);
      alert("Account created successfully.");
      await nav("/app");
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Registration failed.";
    } finally {
      loading.value = false;
    }
  });

  return (
    <div class="auth-shell">
      <div class="auth-card card">
        <div class="card-body">
          <h1 style={{ textAlign: "center", marginTop: 0 }}>Create account</h1>
          <p style={{ textAlign: "center", color: "var(--color-muted)" }}>
            Already have an account? <Link href="/login">Sign in</Link>
          </p>

          <form preventdefault:submit onSubmit$={handleSubmit} style={{ marginTop: "1.5rem" }}>
            <div class="form-group">
              <label for="email">Email</label>
              <input
                id="email"
                class="form-control"
                type="email"
                required
                value={email.value}
                onInput$={(event) => {
                  email.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            <div class="grid grid-2">
              <div class="form-group">
                <label for="first_name">First name</label>
                <input
                  id="first_name"
                  class="form-control"
                  value={firstName.value}
                  onInput$={(event) => {
                    firstName.value = (event.target as HTMLInputElement).value;
                  }}
                />
              </div>
              <div class="form-group">
                <label for="last_name">Last name</label>
                <input
                  id="last_name"
                  class="form-control"
                  value={lastName.value}
                  onInput$={(event) => {
                    lastName.value = (event.target as HTMLInputElement).value;
                  }}
                />
              </div>
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input
                id="password"
                class="form-control"
                type="password"
                minLength={8}
                required
                value={password.value}
                onInput$={(event) => {
                  password.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            {error.value ? <ErrorState message={error.value} /> : null}
            <button type="submit" class="btn btn-primary" style={{ width: "100%" }} disabled={loading.value}>
              {loading.value ? "Creating account…" : "Create account"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
});

export const head: DocumentHead = {
  title: "Register — ULSP",
};
