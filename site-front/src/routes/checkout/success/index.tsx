import { component$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";

export default component$(() => {
  return (
    <div class="container page">
      <section class="card">
        <div class="card-body" style={{ textAlign: "center" }}>
          <h1 style={{ marginTop: 0 }}>Payment successful</h1>
          <p>Thank you for your purchase. Your license should appear shortly in your dashboard.</p>
          <div style={{ display: "flex", justifyContent: "center", gap: "0.75rem", flexWrap: "wrap" }}>
            <Link href="/app/licenses" class="btn btn-primary">
              View licenses
            </Link>
            <Link href="/app" class="btn btn-secondary">
              Go to dashboard
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
});

export const head: DocumentHead = {
  title: "Checkout success — ULSP",
};
