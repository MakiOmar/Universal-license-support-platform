import { component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, unwrapList } from "~/lib/api";
import { customerDisplayName, getCustomer } from "~/lib/auth";
import { EmptyState, ErrorState, LoadingState } from "~/components/site-chrome/site-chrome";
import type { License, Ticket } from "~/lib/types";

export default component$(() => {
  const licenses = useSignal<License[]>([]);
  const tickets = useSignal<Ticket[]>([]);
  const loading = useSignal(true);
  const error = useSignal("");

  useVisibleTask$(async () => {
    loading.value = true;
    error.value = "";
    try {
      const [licenseResponse, ticketResponse] = await Promise.all([
        apiGet<License[] | { data: License[] }>("/customer/licenses"),
        apiGet<Ticket[] | { data: Ticket[] }>("/customer/tickets"),
      ]);
      licenses.value = unwrapList(licenseResponse);
      tickets.value = unwrapList(ticketResponse);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Failed to load dashboard.";
    } finally {
      loading.value = false;
    }
  });

  const customer = getCustomer();
  const activeLicenses = licenses.value.filter((item) => item.status === "active").length;
  const openTickets = tickets.value.filter((item) => !["closed", "resolved"].includes(item.status)).length;

  return (
    <>
      <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, {customerDisplayName(customer)}.</p>
      </div>

      {error.value ? <ErrorState message={error.value} /> : null}
      {loading.value ? (
        <LoadingState />
      ) : (
        <>
          <div class="grid grid-3" style={{ marginBottom: "1.5rem" }}>
            <article class="card">
              <div class="card-body">
                <h2 style={{ marginTop: 0, fontSize: "1rem" }}>Active licenses</h2>
                <p style={{ fontSize: "2rem", fontWeight: 800, margin: 0 }}>{activeLicenses}</p>
              </div>
            </article>
            <article class="card">
              <div class="card-body">
                <h2 style={{ marginTop: 0, fontSize: "1rem" }}>Open tickets</h2>
                <p style={{ fontSize: "2rem", fontWeight: 800, margin: 0 }}>{openTickets}</p>
              </div>
            </article>
            <article class="card">
              <div class="card-body">
                <h2 style={{ marginTop: 0, fontSize: "1rem" }}>Quick actions</h2>
                <div style={{ display: "flex", flexWrap: "wrap", gap: "0.5rem" }}>
                  <Link href="/app/licenses" class="btn btn-secondary">
                    View licenses
                  </Link>
                  <Link href="/app/tickets" class="btn btn-secondary">
                    Open tickets
                  </Link>
                  <Link href="/products" class="btn btn-primary">
                    Buy product
                  </Link>
                </div>
              </div>
            </article>
          </div>

          <section class="card" style={{ marginBottom: "1.5rem" }}>
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Recent licenses</h2>
              {licenses.value.length === 0 ? (
                <EmptyState message="You do not have any licenses yet." />
              ) : (
                <ul>
                  {licenses.value.slice(0, 5).map((license) => (
                    <li key={license.id}>
                      <Link href={`/app/licenses/${license.id}`}>
                        {license.product?.name || `License #${license.id}`}
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Recent tickets</h2>
              {tickets.value.length === 0 ? (
                <EmptyState message="No support tickets yet." />
              ) : (
                <ul>
                  {tickets.value.slice(0, 5).map((ticket) => (
                    <li key={ticket.id}>
                      <Link href={`/app/tickets/${ticket.id}`}>
                        {ticket.ticket_number}: {ticket.subject}
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </section>
        </>
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "Dashboard — ULSP",
};
