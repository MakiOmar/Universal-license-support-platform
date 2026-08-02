import { component$, Slot } from "@builder.io/qwik";
import { SiteNav } from "~/components/site-chrome/site-chrome";

export default component$(() => {
  return (
    <>
      <SiteNav />
      <main>
        <Slot />
      </main>
    </>
  );
});
