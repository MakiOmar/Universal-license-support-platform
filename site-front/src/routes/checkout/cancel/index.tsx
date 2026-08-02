import { component$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";

export default component$(() => {
  return (
    <div class="container page">
      <section class="card">
        <div class="card-body" style={{ textAlign: "center" }}>
          <h1 style={{ marginTop: 0 }}>Checkout canceled</h1>
          <p>Your payment was not completed. You can return to the product catalog and try again.</p>
          <div style={{ display: "flex", justifyContent: "center", gap: "0.75rem", flexWrap: "wrap" }}>
            <Link href="/products" class="btn btn-primary">
              Browse products
            </Link>
            <Link href="/app" class="btn btn-secondary">
              Dashboard
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
});

export const head: DocumentHead = {
  title: "Checkout canceled — ULSP",
};
