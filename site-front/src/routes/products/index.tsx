import { $, component$, useSignal, useVisibleTask$ } from "@builder.io/qwik";
import { Link } from "@builder.io/qwik-city";
import type { DocumentHead } from "@builder.io/qwik-city";
import { apiGet, unwrapList } from "~/lib/api";
import { EmptyState, ErrorState, LoadingState } from "~/components/site-chrome/site-chrome";
import type { Product } from "~/lib/types";

export default component$(() => {
  const products = useSignal<Product[]>([]);
  const loading = useSignal(true);
  const error = useSignal("");
  const search = useSignal("");

  const loadProducts = $(async () => {
    loading.value = true;
    error.value = "";
    try {
      const query = search.value.trim();
      const path = query ? `/products?search=${encodeURIComponent(query)}` : "/products";
      const response = await apiGet<Product[] | { data: Product[] }>(path, false);
      products.value = unwrapList(response);
    } catch (err) {
      error.value = err instanceof Error ? err.message : "Failed to load products.";
      products.value = [];
    } finally {
      loading.value = false;
    }
  });

  useVisibleTask$(async () => {
    await loadProducts();
  });

  return (
    <div class="container page">
      <div class="page-header">
        <h1>Products</h1>
        <p>Browse available software products and pricing tiers.</p>
      </div>

      <div class="form-group">
        <input
          class="form-control"
          type="search"
          placeholder="Search products…"
          value={search.value}
          onInput$={(event) => {
            search.value = (event.target as HTMLInputElement).value;
          }}
          onKeyDown$={async (event) => {
            if (event.key === "Enter") {
              await loadProducts();
            }
          }}
        />
      </div>

      {error.value ? <ErrorState message={error.value} /> : null}
      {loading.value ? (
        <LoadingState />
      ) : products.value.length === 0 ? (
        <EmptyState message="No products found." />
      ) : (
        <div class="grid grid-3">
          {products.value.map((product) => (
            <article key={product.id} class="card">
              <div class="card-body">
                <h2>{product.name}</h2>
                {product.type ? <p style={{ color: "var(--color-muted)" }}>{product.type}</p> : null}
                <p>{product.description || "No description available."}</p>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginTop: "1rem" }}>
                  {product.version ? <span style={{ fontSize: "0.75rem", color: "var(--color-muted)" }}>v{product.version}</span> : <span />}
                  <Link href={`/products/${product.slug || product.id}`} class="btn btn-primary">
                    View details
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </div>
      )}
    </div>
  );
});

export const head: DocumentHead = {
  title: "Products — ULSP",
};
