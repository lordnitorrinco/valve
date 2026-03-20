// Vitest configuration for the frontend unit test suite.
// Uses jsdom for DOM simulation, enables test globals (describe, it, expect).

import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['tests/**/*.test.js'],
    globals: true,
  },
});
