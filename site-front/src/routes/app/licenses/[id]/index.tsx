import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link, useLocation } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import Swal from "sweetalert2";
import { apiDelete, apiGet, unwrapData } from "~/lib/api";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  formatDateTime,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { License, LicenseActivation } from "~/lib/types";

export default component$(() => {
  const loc = useLocation();
  const license = useSignal<License | null>(null);
  const loading = useSignal(true);
  const error = useSignal("");
  const removingId = useSignal<number | null>(null);

  const loadLicense = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<License | { data: License }>(`/customer/licenses/${loc.params.id}`);
      license.value = unwrapData(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "License not found.";
      license.value = null;
    } finally {
      loading.value = false;
    }
  });

  useVisibleTask$(async () => {
    await loadLicense();
  });

  return (
    <>
      <p>
        <Link href="/app/licenses">← Back to licenses</Link>
      </p>

      {loading.value ? (
        <LoadingState />
      ) : error.value ? (
        <ErrorState message={error.value} />
      ) : license.value ? (
        <>
          <section class="card">
            <div class="card-body">
              <div style={{ display: "flex", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}>
                <h1 style={{ margin: 0 }}>{license.value.product?.name || "License details"}</h1>
                <span class={statusBadgeClass(license.value.status)}>{license.value.status}</span>
              </div>

              <div class="grid grid-2" style={{ marginTop: "1.5rem" }}>
                <div>
                  <strong>License key</strong>
                  <p style={{ fontFamily: "monospace", wordBreak: "break-all" }}>{license.value.license_key}</p>
                </div>
                <div>
                  <strong>Activations</strong>
                  <p>
                    {license.value.activations_used ??
                      license.value.activations?.filter((a) => a.status === "active").length ??
                      0}
                    {" / "}
                    {license.value.max_activations ?? "—"}
                  </p>
                </div>
                <div>
                  <strong>Purchased</strong>
                  <p>{formatDate(license.value.purchased_at)}</p>
                </div>
                <div>
                  <strong>Expires</strong>
                  <p>{formatDate(license.value.expires_at)}</p>
                </div>
                <div>
                  <strong>Support expires</strong>
                  <p>{formatDate(license.value.support_expires_at)}</p>
                </div>
                <div>
                  <strong>Pricing tier</strong>
                  <p>{license.value.pricing_tier?.name || "—"}</p>
                </div>
              </div>

              <div style={{ marginTop: "1.5rem", display: "flex", gap: "0.75rem", flexWrap: "wrap" }}>
                <Link href="/app/tickets" class="btn btn-primary">
                  Open support ticket
                </Link>
                <button
                  type="button"
                  class="btn btn-secondary"
                  onClick$={() => {
                    if (license.value?.license_key) {
                      navigator.clipboard?.writeText(license.value.license_key);
                      Swal.fire({ toast: true, position: "top-end", icon: "success", title: "License key copied", showConfirmButton: false, timer: 2000 });
                    }
                  }}
                >
                  Copy license key
                </button>
              </div>
            </div>
          </section>

          <section class="card" style={{ marginTop: "1.5rem" }}>
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Devices</h2>
              <p style={{ color: "var(--color-muted)" }}>
                Remove a lost or old device to free an activation slot, then activate on your new phone.
              </p>

              {(license.value.activations || []).length === 0 ? (
                <EmptyState message="No devices activated yet." />
              ) : (
                <div style={{ display: "grid", gap: "0.75rem" }}>
                  {(license.value.activations || []).map((activation: LicenseActivation) => {
                    const modelName = activation.device_name?.trim() || "";
                    const metaBits = [
                      activation.platform,
                      activation.app_version ? `v${activation.app_version}` : "",
                    ].filter(Boolean);

                    return (
                      <div
                        key={activation.id}
                        class="card"
                        style={{ padding: "0.75rem 1rem", display: "flex", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}
                      >
                        <div>
                          {/* Prefer phone model from device meta when the app sent it. */}
                          <strong>{modelName || activation.activation_value}</strong>
                          {modelName ? (
                            <p style={{ margin: "0.25rem 0", color: "var(--color-muted)", fontFamily: "monospace", fontSize: "0.85rem", wordBreak: "break-all" }}>
                              {activation.activation_value}
                            </p>
                          ) : null}
                          <p style={{ margin: "0.25rem 0", color: "var(--color-muted)" }}>
                            {metaBits.length > 0
                              ? metaBits.join(" · ")
                              : activation.activation_type}
                          </p>
                          <p style={{ margin: 0 }}>
                            <span class={statusBadgeClass(activation.status)}>{activation.status}</span>
                            {" · Last check: "}
                            {formatDateTime(activation.last_check_at)}
                          </p>
                        </div>
                        {activation.status === "active" ? (
                          <button
                            type="button"
                            class="btn btn-secondary"
                            disabled={removingId.value === activation.id}
                            onClick$={async () => {
                              const result = await Swal.fire({
                                title: "Remove this device?",
                                text: "You can activate again on a new device after removing this one.",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Remove device",
                              });
                              if (!result.isConfirmed || !license.value) return;
                              removingId.value = activation.id;
                              try {
                                await apiDelete(`/customer/licenses/${license.value.id}/activations/${activation.id}`);
                                await Swal.fire({ toast: true, position: "top-end", icon: "success", title: "Device removed", showConfirmButton: false, timer: 2000 });
                                await loadLicense();
                              } catch (err) {
                                await Swal.fire({
                                  icon: "error",
                                  title: "Could not remove device",
                                  text: err instanceof Error ? err.message : "Request failed",
                                });
                              } finally {
                                removingId.value = null;
                              }
                            }}
                          >
                            {removingId.value === activation.id ? "Removing…" : "Remove"}
                          </button>
                        ) : null}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </section>
        </>
      ) : (
        <EmptyState message="License not found." />
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "License details — ULSP",
};
