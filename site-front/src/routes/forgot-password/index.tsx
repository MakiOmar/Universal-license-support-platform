import { $, component$, useSignal } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiPost } from "~/lib/api";
import { ErrorState } from "~/components/site-chrome/site-chrome";

export default component$(() => {
  const email = useSignal("");
  const loading = useSignal(false);
  const error = useSignal("");
  const success = useSignal("");

  const handleSubmit = $(async () => {
    loading.value = true;
    error.value = "";
    success.value = "";
    try {
      await apiPost("/auth/forgot-password", { email: email.value }, false);
      success.value = "If an account exists for that email, a reset link has been sent.";
      alert(success.value);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Unable to send reset email.";
    } finally {
      loading.value = false;
    }
  });

  return (
    <div class="auth-shell">
      <div class="auth-card card">
        <div class="card-body">
          <h1 style={{ textAlign: "center", marginTop: 0 }}>Forgot password</h1>
          <p style={{ textAlign: "center", color: "var(--color-muted)" }}>
            Enter your email and we will send reset instructions.
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
            {error.value ? <ErrorState message={error.value} /> : null}
            {success.value ? <div class="alert alert-success">{success.value}</div> : null}
            <button type="submit" class="btn btn-primary" style={{ width: "100%" }} disabled={loading.value}>
              {loading.value ? "Sending…" : "Send reset link"}
            </button>
          </form>

          <p style={{ textAlign: "center", marginTop: "1rem" }}>
            <Link href="/login">Back to login</Link>
          </p>
        </div>
      </div>
    </div>
  );
});

export const head: DocumentHead = {
  title: "Forgot password — ULSP",
};
