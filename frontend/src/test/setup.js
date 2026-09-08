import "@testing-library/jest-dom/vitest";
import { afterEach } from "vitest";
import { cleanup } from "@testing-library/react";

// jsdom does not implement IntersectionObserver, which framer-motion's
// whileInView prop relies on for every animated section on the homepage.
class MockIntersectionObserver {
  observe() {}
  unobserve() {}
  disconnect() {}
}
globalThis.IntersectionObserver = MockIntersectionObserver;

afterEach(() => {
  cleanup();
  localStorage.clear();
});
