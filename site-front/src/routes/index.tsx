import { component$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";

export default component$(() => {
  return (
    <div class="container page hero">
      <h1>Universal License &amp; Support Platform</h1>
      <p>
        Purchase software licenses, manage activations, and get help from one customer portal.
        Browse products, secure checkout, and track support tickets in one place.
      </p>
      <div class="hero-actions">
        <Link href="/products" class="btn btn-primary">
          Browse products
        </Link>
        <Link href="/register" class="btn btn-secondary">
          Create account
        </Link>
        <Link href="/app" class="btn btn-secondary">
          Customer dashboard
        </Link>
      </div>
    </div>
  );
});

export const head: DocumentHead = {
  title: "ULSP — Customer Portal",
  meta: [
    {
      name: "description",
      content: "Purchase licenses, manage activations, and open support tickets.",
    },
  ],
};
