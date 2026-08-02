import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import Swal from "sweetalert2";
import { apiGet, apiPost, unwrapList } from "~/lib/api";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { License, Product, Ticket } from "~/lib/types";

function uniqueLicensedProducts(items: License[]): Product[] {
  const map = new Map<number, Product>();
  for (const license of items) {
    if (license.product?.id) {
      map.set(license.product.id, license.product);
    }
  }
  return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
}

function licensesForSelectedProduct(items: License[], selectedProductId: string): License[] {
  const selected = Number(selectedProductId);
  if (!selected) return [];
  return items.filter((license) => {
    const productId = license.product?.id ?? (license as License & { product_id?: number }).product_id;
    return productId === selected;
  });
}

export default component$(() => {
  const tickets = useSignal<Ticket[]>([]);
  const licenses = useSignal<License[]>([]);
  const licensedProducts = useSignal<Product[]>([]);
  const productLicenses = useSignal<License[]>([]);
  const loading = useSignal(true);
  const error = useSignal("");
  const showCreate = useSignal(false);
  const creating = useSignal(false);
  const createError = useSignal("");

  const subject = useSignal("");
  const description = useSignal("");
  const priority = useSignal("medium");
  const category = useSignal("");
  const productId = useSignal("");
  const licenseId = useSignal("");
  const statusFilter = useSignal("");

  const syncProductOptions = $(() => {
    licensedProducts.value = uniqueLicensedProducts(licenses.value);
    productLicenses.value = licensesForSelectedProduct(licenses.value, productId.value);
  });

  const loadTickets = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const query = statusFilter.value ? `?status=${encodeURIComponent(statusFilter.value)}` : "";
      const response = await apiGet<Ticket[] | { data: Ticket[] }>(`/customer/tickets${query}`);
      tickets.value = unwrapList(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Failed to load tickets.";
    } finally {
      loading.value = false;
    }
  });

  const loadLicenses = $(async () => {
    try {
      const response = await apiGet<License[] | { data: License[] }>("/customer/licenses");
      licenses.value = unwrapList(response);
      await syncProductOptions();
    } catch {
      licenses.value = [];
      licensedProducts.value = [];
      productLicenses.value = [];
    }
  });

  useVisibleTask$(async () => {
    await Promise.all([loadTickets(), loadLicenses()]);
  });

  const openCreate = $(async () => {
    createError.value = "";
    if (licenses.value.length === 0) {
      await loadLicenses();
    } else {
      await syncProductOptions();
    }

    if (licensedProducts.value.length === 0) {
      await Swal.fire({
        icon: "info",
        title: "No licensed products",
        text: "Purchase a license before opening a support ticket.",
      });
      return;
    }

    showCreate.value = true;
  });

  const handleCreate = $(async () => {
    if (!productId.value) {
      createError.value = "Please select a licensed product.";
      return;
    }

    creating.value = true;
    createError.value = "";
    try {
      const payload: Record<string, string | number> = {
        subject: subject.value,
        description: description.value,
        priority: priority.value,
        product_id: Number(productId.value),
      };
      if (category.value) payload.category = category.value;
      if (licenseId.value) payload.license_id = Number(licenseId.value);

      await apiPost("/customer/tickets", payload);
      await Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: "Ticket created",
        showConfirmButton: false,
        timer: 2000,
      });
      showCreate.value = false;
      subject.value = "";
      description.value = "";
      priority.value = "medium";
      category.value = "";
      productId.value = "";
      licenseId.value = "";
      productLicenses.value = [];
      await loadTickets();
    } catch (err) {
      createError.value = err instanceof Error ? err.message : "Failed to create ticket.";
    } finally {
      creating.value = false;
    }
  });

  return (
    <>
      <div class="page-header" style={{ display: "flex", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}>
        <div>
          <h1>Support tickets</h1>
          <p>Manage your support requests.</p>
        </div>
        <button type="button" class="btn btn-primary" onClick$={openCreate}>
          New ticket
        </button>
      </div>

      <div class="form-group" style={{ maxWidth: "16rem" }}>
        <label for="status">Filter by status</label>
        <select
          id="status"
          class="form-control"
          value={statusFilter.value}
          onChange$={async (event) => {
            statusFilter.value = (event.target as HTMLSelectElement).value;
            await loadTickets();
          }}
        >
          <option value="">All statuses</option>
          <option value="open">Open</option>
          <option value="in_progress">In progress</option>
          <option value="waiting_customer">Waiting customer</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      {error.value ? <ErrorState message={error.value} /> : null}
      {loading.value ? (
        <LoadingState />
      ) : tickets.value.length === 0 ? (
        <EmptyState message="No tickets found." />
      ) : (
        <div class="card table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Product</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {tickets.value.map((ticket) => (
                <tr key={ticket.id}>
                  <td>{ticket.ticket_number}</td>
                  <td>{ticket.subject}</td>
                  <td>{ticket.product?.name || "—"}</td>
                  <td>
                    <span class={statusBadgeClass(ticket.priority)}>{ticket.priority}</span>
                  </td>
                  <td>
                    <span class={statusBadgeClass(ticket.status)}>{ticket.status}</span>
                  </td>
                  <td>{formatDate(ticket.created_at)}</td>
                  <td>
                    <Link href={`/app/tickets/${ticket.id}`}>View</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {showCreate.value ? (
        <div class="modal-backdrop" onClick$={() => !creating.value && (showCreate.value = false)}>
          <div class="modal card" onClick$={(event) => event.stopPropagation()}>
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Create ticket</h2>
              <form preventdefault:submit onSubmit$={handleCreate}>
                <div class="form-group">
                  <label for="product_id">Product</label>
                  <select
                    id="product_id"
                    class="form-control"
                    required
                    value={productId.value}
                    onChange$={(event) => {
                      productId.value = (event.target as HTMLSelectElement).value;
                      licenseId.value = "";
                      productLicenses.value = licensesForSelectedProduct(licenses.value, productId.value);
                      if (productLicenses.value.length === 1) {
                        licenseId.value = String(productLicenses.value[0].id);
                      }
                    }}
                  >
                    <option value="">Select a licensed product</option>
                    {licensedProducts.value.map((product) => (
                      <option key={product.id} value={product.id}>
                        {product.name}
                      </option>
                    ))}
                  </select>
                  <p style={{ margin: "0.35rem 0 0", color: "var(--color-muted)", fontSize: "0.875rem" }}>
                    Only products you already have a license for are listed.
                  </p>
                </div>

                {productId.value ? (
                  <div class="form-group">
                    <label for="license_id">License (optional)</label>
                    <select
                      id="license_id"
                      class="form-control"
                      value={licenseId.value}
                      onChange$={(event) => {
                        licenseId.value = (event.target as HTMLSelectElement).value;
                      }}
                    >
                      <option value="">Any / not specific</option>
                      {productLicenses.value.map((license) => (
                        <option key={license.id} value={license.id}>
                          {license.license_key}
                        </option>
                      ))}
                    </select>
                  </div>
                ) : null}

                <div class="form-group">
                  <label for="subject">Subject</label>
                  <input
                    id="subject"
                    class="form-control"
                    required
                    value={subject.value}
                    onInput$={(event) => {
                      subject.value = (event.target as HTMLInputElement).value;
                    }}
                  />
                </div>
                <div class="form-group">
                  <label for="description">Description</label>
                  <textarea
                    id="description"
                    class="form-control"
                    rows={5}
                    required
                    value={description.value}
                    onInput$={(event) => {
                      description.value = (event.target as HTMLTextAreaElement).value;
                    }}
                  />
                </div>
                <div class="grid grid-2">
                  <div class="form-group">
                    <label for="priority">Priority</label>
                    <select
                      id="priority"
                      class="form-control"
                      value={priority.value}
                      onChange$={(event) => {
                        priority.value = (event.target as HTMLSelectElement).value;
                      }}
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="category">Category</label>
                    <select
                      id="category"
                      class="form-control"
                      value={category.value}
                      onChange$={(event) => {
                        category.value = (event.target as HTMLSelectElement).value;
                      }}
                    >
                      <option value="">Select category</option>
                      <option value="technical">Technical</option>
                      <option value="billing">Billing</option>
                      <option value="feature_request">Feature request</option>
                      <option value="bug_report">Bug report</option>
                      <option value="account">Account</option>
                      <option value="license">License</option>
                    </select>
                  </div>
                </div>
                {createError.value ? <ErrorState message={createError.value} /> : null}
                <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.5rem" }}>
                  <button
                    type="button"
                    class="btn btn-secondary"
                    disabled={creating.value}
                    onClick$={() => (showCreate.value = false)}
                  >
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-primary" disabled={creating.value}>
                    {creating.value ? "Creating…" : "Create ticket"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
});

export const head: DocumentHead = {
  title: "Tickets — ULSP",
};
