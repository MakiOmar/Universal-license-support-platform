import { component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link, useLocation } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, unwrapData } from "~/lib/api";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { License } from "~/lib/types";

export default component$(() => {
  const loc = useLocation();
  const license = useSignal<License | null>(null);
  const loading = useSignal(true);
  const error = useSignal("");

  useVisibleTask$(async () => {
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
                <strong>Max activations</strong>
                <p>{license.value.max_activations ?? "—"}</p>
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
                    alert("License key copied.");
                  }
                }}
              >
                Copy license key
              </button>
            </div>
          </div>
        </section>
      ) : (
        <EmptyState message="License not found." />
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "License details — ULSP",
};
