import { component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, unwrapList } from "~/lib/api";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { Payment } from "~/lib/types";

export default component$(() => {
  const payments = useSignal<Payment[]>([]);
  const loading = useSignal(true);
  const error = useSignal("");

  useVisibleTask$(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<Payment[] | { data: Payment[] }>("/customer/payments");
      payments.value = unwrapList(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Could not load payments.";
      payments.value = [];
    } finally {
      loading.value = false;
    }
  });

  return (
    <section class="card">
      <div class="card-body">
        <h1 style={{ marginTop: 0 }}>Payments</h1>
        {loading.value ? (
          <LoadingState />
        ) : error.value ? (
          <ErrorState message={error.value} />
        ) : payments.value.length === 0 ? (
          <EmptyState message="No payments yet." />
        ) : (
          <div style={{ display: "grid", gap: "0.75rem" }}>
            {payments.value.map((payment) => (
              <article key={payment.id} class="card" style={{ padding: "1rem" }}>
                <div style={{ display: "flex", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}>
                  <div>
                    <strong>
                      {payment.currency} {payment.amount}
                    </strong>
                    <p style={{ margin: "0.25rem 0", color: "var(--color-muted)" }}>
                      {payment.product?.name || payment.pricing_tier?.name || "Purchase"}
                    </p>
                    <p style={{ margin: 0 }}>{formatDate(payment.paid_at || payment.created_at)}</p>
                  </div>
                  <span class={statusBadgeClass(payment.status)}>{payment.status}</span>
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </section>
  );
});

export const head: DocumentHead = {
  title: "Payments — ULSP",
};
