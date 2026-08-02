import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import type { DocumentHead } from "@builder.io/qwik-city";
import Swal from "sweetalert2";
import { apiGet, apiPost, apiPut, unwrapData } from "~/lib/api";
import { getCustomer, getToken, setAuth, setCustomer } from "~/lib/auth";
import { ErrorState, LoadingState } from "~/components/site-chrome/site-chrome";
import type { Customer } from "~/lib/types";

export default component$(() => {
  const loading = useSignal(true);
  const saving = useSignal(false);
  const error = useSignal("");
  const verifiedAt = useSignal<string | null>(null);
  const verifyCode = useSignal("");
  const verifyBusy = useSignal(false);

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
    verifiedAt.value = customer.email_verified_at || null;
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
      await applyCustomer(updated);
      password.value = "";
      passwordConfirmation.value = "";
      await Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: "Profile updated",
        showConfirmButton: false,
        timer: 2000,
      });
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
        <>
          {!verifiedAt.value ? (
            <section class="card" style={{ marginBottom: "1.5rem" }}>
              <div class="card-body">
                <h2 style={{ marginTop: 0 }}>Verify email</h2>
                <p>Email verification is required before purchasing licenses.</p>
                <div class="form-group">
                  <label for="verify_code">Verification code</label>
                  <input
                    id="verify_code"
                    class="form-control"
                    value={verifyCode.value}
                    onInput$={(event) => {
                      verifyCode.value = (event.target as HTMLInputElement).value;
                    }}
                  />
                </div>
                <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
                  <button
                    type="button"
                    class="btn btn-secondary"
                    disabled={verifyBusy.value}
                    onClick$={async () => {
                      verifyBusy.value = true;
                      try {
                        await apiPost("/auth/email/verification-notification");
                        await Swal.fire({
                          toast: true,
                          position: "top-end",
                          icon: "success",
                          title: "Code sent",
                          showConfirmButton: false,
                          timer: 2000,
                        });
                      } catch (err) {
                        await Swal.fire({
                          icon: "error",
                          title: "Could not send code",
                          text: err instanceof Error ? err.message : "Failed",
                        });
                      } finally {
                        verifyBusy.value = false;
                      }
                    }}
                  >
                    Resend code
                  </button>
                  <button
                    type="button"
                    class="btn btn-primary"
                    disabled={verifyBusy.value || verifyCode.value.length !== 6}
                    onClick$={async () => {
                      verifyBusy.value = true;
                      try {
                        const response = await apiPost<{ customer: Customer }>("/auth/email/verify", {
                          code: verifyCode.value,
                        });
                        if (response.customer) {
                          setCustomer(response.customer);
                          await applyCustomer(response.customer);
                        }
                        await Swal.fire({
                          toast: true,
                          position: "top-end",
                          icon: "success",
                          title: "Email verified",
                          showConfirmButton: false,
                          timer: 2000,
                        });
                      } catch (err) {
                        await Swal.fire({
                          icon: "error",
                          title: "Verification failed",
                          text: err instanceof Error ? err.message : "Failed",
                        });
                      } finally {
                        verifyBusy.value = false;
                      }
                    }}
                  >
                    Verify
                  </button>
                </div>
              </div>
            </section>
          ) : (
            <p style={{ color: "var(--color-muted)" }}>Email verified.</p>
          )}

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
        </>
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "Profile — ULSP",
};
