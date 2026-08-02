import { component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, unwrapList } from "~/lib/api";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { License } from "~/lib/types";

export default component$(() => {
  const licenses = useSignal<License[]>([]);
  const loading = useSignal(true);
  const error = useSignal("");

  useVisibleTask$(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<License[] | { data: License[] }>("/customer/licenses");
      licenses.value = unwrapList(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Failed to load licenses.";
    } finally {
      loading.value = false;
    }
  });

  return (
    <>
      <div class="page-header">
        <h1>My licenses</h1>
        <p>Manage your software licenses and activations.</p>
      </div>

      {error.value ? <ErrorState message={error.value} /> : null}
      {loading.value ? (
        <LoadingState />
      ) : licenses.value.length === 0 ? (
        <EmptyState message="You do not have any licenses yet." />
      ) : (
        <div class="grid grid-3">
          {licenses.value.map((license) => (
            <article key={license.id} class="card">
              <div class="card-body">
                <div style={{ display: "flex", justifyContent: "space-between", gap: "0.5rem" }}>
                  <h2 style={{ margin: 0, fontSize: "1.125rem" }}>{license.product?.name || "License"}</h2>
                  <span class={statusBadgeClass(license.status)}>{license.status}</span>
                </div>
                <p style={{ fontFamily: "monospace", fontSize: "0.875rem", wordBreak: "break-all" }}>
                  {license.license_key}
                </p>
                {license.expires_at ? <p>Expires: {formatDate(license.expires_at)}</p> : null}
                <Link href={`/app/licenses/${license.id}`} class="btn btn-primary" style={{ width: "100%" }}>
                  View details
                </Link>
              </div>
            </article>
          ))}
        </div>
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "Licenses — ULSP",
};
