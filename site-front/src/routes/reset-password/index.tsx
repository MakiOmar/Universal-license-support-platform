import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link, useLocation, useNavigate } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiPost } from "~/lib/api";
import { ErrorState } from "~/components/site-chrome/site-chrome";

export default component$(() => {
  const loc = useLocation();
  const nav = useNavigate();

  const token = useSignal("");
  const email = useSignal("");
  const password = useSignal("");
  const passwordConfirmation = useSignal("");
  const loading = useSignal(false);
  const error = useSignal("");

  useVisibleTask$(() => {
    const params = loc.url.searchParams;
    token.value = params.get("token") || "";
    email.value = params.get("email") || "";
  });

  const handleSubmit = $(async () => {
    if (password.value !== passwordConfirmation.value) {
      error.value = "Passwords do not match.";
      return;
    }

    loading.value = true;
    error.value = "";
    try {
      await apiPost(
        "/auth/reset-password",
        {
          token: token.value,
          email: email.value,
          password: password.value,
          password_confirmation: passwordConfirmation.value,
        },
        false,
      );
      alert("Password reset successfully. You can now sign in.");
      await nav("/login");
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Unable to reset password.";
    } finally {
      loading.value = false;
    }
  });

  return (
    <div class="auth-shell">
      <div class="auth-card card">
        <div class="card-body">
          <h1 style={{ textAlign: "center", marginTop: 0 }}>Reset password</h1>

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
            <div class="form-group">
              <label for="token">Reset token</label>
              <input
                id="token"
                class="form-control"
                required
                value={token.value}
                onInput$={(event) => {
                  token.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            <div class="form-group">
              <label for="password">New password</label>
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
            <div class="form-group">
              <label for="password_confirmation">Confirm password</label>
              <input
                id="password_confirmation"
                class="form-control"
                type="password"
                required
                value={passwordConfirmation.value}
                onInput$={(event) => {
                  passwordConfirmation.value = (event.target as HTMLInputElement).value;
                }}
              />
            </div>
            {error.value ? <ErrorState message={error.value} /> : null}
            <button type="submit" class="btn btn-primary" style={{ width: "100%" }} disabled={loading.value}>
              {loading.value ? "Saving…" : "Reset password"}
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
  title: "Reset password — ULSP",
};
