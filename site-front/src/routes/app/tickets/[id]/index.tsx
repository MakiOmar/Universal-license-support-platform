import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link, useLocation } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, apiPostForm, getApiBase, unwrapData } from "~/lib/api";
import { getToken } from "~/lib/auth";
import {
  EmptyState,
  ErrorState,
  LoadingState,
  formatDate,
  statusBadgeClass,
} from "~/components/site-chrome/site-chrome";
import type { Ticket } from "~/lib/types";

export default component$(() => {
  const loc = useLocation();
  const ticket = useSignal<Ticket | null>(null);
  const loading = useSignal(true);
  const error = useSignal("");
  const replyMessage = useSignal("");
  const replyFiles = useSignal<File[]>([]);
  const replyLoading = useSignal(false);
  const replyError = useSignal("");

  const loadTicket = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<Ticket | { data: Ticket }>(`/customer/tickets/${loc.params.id}`);
      ticket.value = unwrapData(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Ticket not found.";
      ticket.value = null;
    } finally {
      loading.value = false;
    }
  });

  useVisibleTask$(async () => {
    await loadTicket();
  });

  const handleReply = $(async () => {
    if (!replyMessage.value.trim()) return;
    replyLoading.value = true;
    replyError.value = "";
    try {
      const formData = new FormData();
      formData.append("message", replyMessage.value);
      for (const file of replyFiles.value) {
        formData.append("attachments[]", file);
      }
      await apiPostForm(`/customer/tickets/${loc.params.id}/replies`, formData);
      replyMessage.value = "";
      replyFiles.value = [];
      await loadTicket();
    } catch (err) {
      replyError.value = err instanceof Error ? err.message : "Failed to send reply.";
    } finally {
      replyLoading.value = false;
    }
  });

  const publicReplies = ticket.value?.replies?.filter((reply) => !reply.is_internal) ?? [];

  return (
    <>
      <p>
        <Link href="/app/tickets">← Back to tickets</Link>
      </p>

      {loading.value ? (
        <LoadingState />
      ) : error.value ? (
        <ErrorState message={error.value} />
      ) : ticket.value ? (
        <>
          <section class="card" style={{ marginBottom: "1.5rem" }}>
            <div class="card-body">
              <div style={{ display: "flex", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}>
                <div>
                  <h1 style={{ margin: "0 0 0.25rem" }}>{ticket.value.subject}</h1>
                  <p style={{ margin: 0, color: "var(--color-muted)" }}>{ticket.value.ticket_number}</p>
                </div>
                <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
                  <span class={statusBadgeClass(ticket.value.priority)}>{ticket.value.priority}</span>
                  <span class={statusBadgeClass(ticket.value.status)}>{ticket.value.status}</span>
                </div>
              </div>
              <p style={{ marginTop: "1rem", whiteSpace: "pre-wrap" }}>{ticket.value.description}</p>
              {(ticket.value.attachments || []).length > 0 ? (
                <ul>
                  {ticket.value.attachments!.map((attachment) => (
                    <li key={attachment.id}>
                      <button
                        type="button"
                        class="btn btn-secondary"
                        onClick$={async () => {
                          const token = getToken();
                          const response = await fetch(
                            `${getApiBase()}/customer/tickets/${ticket.value!.id}/attachments/${attachment.id}`,
                            { headers: token ? { Authorization: `Bearer ${token}` } : {} },
                          );
                          const blob = await response.blob();
                          const url = URL.createObjectURL(blob);
                          const anchor = document.createElement("a");
                          anchor.href = url;
                          anchor.download = attachment.filename;
                          anchor.click();
                          URL.revokeObjectURL(url);
                        }}
                      >
                        {attachment.filename}
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </section>

          <section class="card" style={{ marginBottom: "1.5rem" }}>
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Conversation</h2>
              {publicReplies.length === 0 ? (
                <EmptyState message="No replies yet." />
              ) : (
                <div class="thread">
                  {publicReplies.map((reply) => (
                    <article key={reply.id} class="thread-item">
                      <div class="thread-meta">
                        {reply.author_type} · {formatDate(reply.created_at)}
                      </div>
                      <p style={{ margin: 0, whiteSpace: "pre-wrap" }}>{reply.message}</p>
                    </article>
                  ))}
                </div>
              )}
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <h2 style={{ marginTop: 0 }}>Add reply</h2>
              <form preventdefault:submit onSubmit$={handleReply}>
                <div class="form-group">
                  <label for="message">Message</label>
                  <textarea
                    id="message"
                    class="form-control"
                    rows={4}
                    required
                    value={replyMessage.value}
                    onInput$={(event) => {
                      replyMessage.value = (event.target as HTMLTextAreaElement).value;
                    }}
                  />
                </div>
                <div class="form-group">
                  <label for="attachments">Attachments</label>
                  <input
                    id="attachments"
                    class="form-control"
                    type="file"
                    multiple
                    onChange$={(event) => {
                      const input = event.target as HTMLInputElement;
                      replyFiles.value = input.files ? Array.from(input.files) : [];
                    }}
                  />
                </div>
                {replyError.value ? <ErrorState message={replyError.value} /> : null}
                <button type="submit" class="btn btn-primary" disabled={replyLoading.value}>
                  {replyLoading.value ? "Sending…" : "Send reply"}
                </button>
              </form>
            </div>
          </section>
        </>
      ) : (
        <EmptyState message="Ticket not found." />
      )}
    </>
  );
});

export const head: DocumentHead = {
  title: "Ticket — ULSP",
};
