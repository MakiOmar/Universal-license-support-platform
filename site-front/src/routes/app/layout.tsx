import { component$, Slot, useVisibleTask$ } from "@builder.io/qwik";
import { useNavigate } from "@builder.io/qwik-city";
import { AppSubNav } from "~/components/site-chrome/site-chrome";
import { isLoggedIn } from "~/lib/auth";

export default component$(() => {
  const nav = useNavigate();

  useVisibleTask$(() => {
    if (!isLoggedIn()) {
      nav("/login");
    }
  });

  return (
    <div class="container page">
      <AppSubNav />
      <Slot />
    </div>
  );
});
