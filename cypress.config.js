import cypress, { defineConfig } from "cypress";

export default defineConfig({
  viewportWidth: 1920,
  viewportHeight: 1080,
  allowCypressEnv: false,
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here,
    },
    baseUrl: process.env.baseUrl
    
  },
});
