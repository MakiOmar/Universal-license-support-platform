import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link, useLocation } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, apiPost, unwrapData } from "~/lib/api";
import { isLoggedIn } from "~/lib/auth";
import { EmptyState, ErrorState, LoadingState, statusBadgeClass } from "~/components/site-chrome/site-chrome";
import type { PricingTier, Product } from "~/lib/types";

export default component$(() => {
  const loc = useLocation();
  const slug = loc.params.slug;

  const product = useSignal<Product | null>(null);
  const loading = useSignal(true);
  const error = useSignal("");
  const checkoutError = useSignal("");
  const checkoutLoading = useSignal<number | null>(null);

  useVisibleTask$(async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await apiGet<Product | { data: Product }>(`/products/${slug}`, false);
      product.value = unwrapData(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Product not found.";
      product.value = null;
    } finally {
      loading.value = false;
    }
  });

  const startCheckout = $(async (tier: PricingTier) => {
    if (!isLoggedIn()) {
      window.location.href = "/login";
      return;
    }

    checkoutLoading.value = tier.id;
    checkoutError.value = "";
    try {
      const response = await apiPost<{ url: string } | { data: { url: string } }>("/checkout/session", {
        pricing_tier_id: tier.id,
      });
      const session = unwrapData(response);
      if (session.url) {
        window.location.href = session.url;
        return;
      }
      throw new Error("Checkout URL was not returned.");
    } catch (err) {
      checkoutError.value = err instanceof Error ? err.message : "Unable to start checkout.";
      alert(checkoutError.value);
    } finally {
      checkoutLoading.value = null;
    }
  });

  return (
    <div class="container page">
      <p>
        <Link href="/products">← Back to products</Link>
      </p>

      {loading.value ? (
        <LoadingState />
      ) : error.value ? (
        <ErrorState message={error.value} />
      ) : product.value ? (
        <div class="grid" style={{ gap: "1.5rem" }}>
          <section class="card">
            <div class="card-body">
              <h1>{product.value.name}</h1>
              {product.value.type ? <p style={{ color: "var(--color-muted)" }}>{product.value.type}</p> : null}
              <div style={{ display: "flex", gap: "1rem", flexWrap: "wrap", margin: "1rem 0" }}>
                {product.value.version ? <span>Version: {product.value.version}</span> : null}
                {product.value.status ? (
                  <span class={statusBadgeClass(product.value.status)}>{product.value.status}</span>
                ) : null}
              </div>
              {product.value.description ? <p style={{ whiteSpace: "pre-wrap" }}>{product.value.description}</p> : null}
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <h2>Get a license</h2>
              {!isLoggedIn() ? (
                <div class="alert alert-info">
                  Please <Link href="/login">log in</Link> or <Link href="/register">register</Link> to purchase.
                </div>
              ) : null}

              {checkoutError.value ? <ErrorState message={checkoutError.value} /> : null}

              {product.value.pricing_tiers && product.value.pricing_tiers.length > 0 ? (
                <div class="grid grid-2">
                  {product.value.pricing_tiers.map((tier) => (
                    <article key={tier.id} class="card">
                      <div class="card-body">
                        <h3>{tier.name}</h3>
                        <p style={{ fontSize: "1.25rem", fontWeight: 700 }}>
                          {tier.currency} {tier.price}
                        </p>
                        {tier.billing_cycle_label || tier.billing_cycle ? (
                          <p style={{ color: "var(--color-muted)" }}>
                            {tier.billing_cycle_label || tier.billing_cycle}
                          </p>
                        ) : null}
                        {tier.max_activations ? <p>Up to {tier.max_activations} activations</p> : null}
                        <button
                          type="button"
                          class="btn btn-primary"
                          disabled={checkoutLoading.value === tier.id}
                          onClick$={() => startCheckout(tier)}
                        >
                          {checkoutLoading.value === tier.id ? "Redirecting…" : "Buy now"}
                        </button>
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <EmptyState message="No pricing tiers are available for this product yet." />
              )}
            </div>
          </section>
        </div>
      ) : (
        <EmptyState message="Product not found." />
      )}
    </div>
  );
});

export const head: DocumentHead = {
  title: "Product — ULSP",
};
