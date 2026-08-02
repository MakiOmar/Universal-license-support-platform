import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, apiPut, unwrapData } from "~/lib/api";
import { getCustomer, getToken, setAuth } from "~/lib/auth";
import { ErrorState, LoadingState } from "~/components/site-chrome/site-chrome";
import type { Customer } from "~/lib/types";

export default component$(() => {
  const loading = useSignal(true);
  const saving = useSignal(false);
  const error = useSignal("");

  const email = useSignal("");
  const firstName = useSignal("");
  const lastName = useSignal("");
  const company = useSignal("");
  const phone = useSignal("");
  const password = useSignal("");
  const passwordConfirmation = useSignal("");

  const applyCustomer = $((customer: Customer) => {
    email.value = customer.email || "";
    firstName.value = customer.first_name || "";
    lastName.value = customer.last_name || "";
    company.value = customer.company || "";
    phone.value = customer.phone || "";
  });

  useVisibleTask$(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<Customer | { data: Customer }>("/customer/me");
      await applyCustomer(unwrapData(response));
    } catch (err) {
      const stored = getCustomer();
      if (stored) {
        await applyCustomer(stored);
      } else {
        error.value = err instanceof Error ? err.message : "Failed to load profile.";
      }
    } finally {
      loading.value = false;
    }
  });

  const handleSubmit = $(async () => {
    if (password.value && password.value !== passwordConfirmation.value) {
      error.value = "Passwords do not match.";
      return;
    }

    saving.value = true;
    error.value = "";
    try {
      const payload: Record<string, string> = {
        email: email.value,
        first_name: firstName.value,
        last_name: lastName.value,
        company: company.value,
        phone: phone.value,
      };
      if (password.value) {
        payload.password = password.value;
        payload.password_confirmation = passwordConfirmation.value;
      }

      const response = await apiPut<Customer | { data: Customer }>("/customer/profile", payload);
      const updated = unwrapData(response);
      const token = getToken();
      if (token) {
        setAuth(token, updated);
      }
      password.value = "";
      passwordConfirmation.value = "";
      alert("Profile updated successfully.");
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Failed to update profile.";
    } finally {
      saving.value = false;
    }
  });

  return (
    <>
      <div class="page-header">
        <h1>Profile</h1>
        <p>Manage your account information.</p>
      </div>

      {loading.value ? (
        <LoadingState />
      ) : (
        <section class="card">
          <div class="card-body">
            <form preventdefault:submit onSubmit$={handleSubmit}>
              <div class="grid grid-2">
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
                  <label for="phone">Phone</label>
                  <input
                    id="phone"
                    class="form-control"
                    value={phone.value}
                    onInput$={(event) => {
                      phone.value = (event.target as HTMLInputElement).value;
                    }}
                  />
                </div>
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
                <div class="form-group" style={{ gridColumn: "1 / -1" }}>
                  <label for="company">Company</label>
                  <input
                    id="company"
                    class="form-control"
                    value={company.value}
                    onInput$={(event) => {
                      company.value = (event.target as HTMLInputElement).value;
                    }}
                  />
                </div>
              </div>

              <h2 style={{ marginTop: "1.5rem" }}>Change password</h2>
              <div class="grid grid-2">
                <div class="form-group">
                  <label for="password">New password</label>
                  <input
                    id="password"
                    class="form-control"
                    type="password"
                    minLength={8}
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
                    value={passwordConfirmation.value}
                    onInput$={(event) => {
                      passwordConfirmation.value = (event.target as HTMLInputElement).value;
                    }}
                  />
                </div>
              </div>

              {error.value ? <ErrorState message={error.value} /> : null}

              <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.5rem", marginTop: "1rem" }}>
                <button type="submit" class="btn btn-primary" disabled={saving.value}>
                  {saving.value ? "Saving…" : "Save changes"}
                </button>
              </div>
            </form>
          </div>
        </section>
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "Profile — ULSP",
};
