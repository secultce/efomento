import { defineConfig } from "cypress";

export default defineConfig({
  viewportWidth: 1920,
  viewportHeight: 1080,
  allowCypressEnv: false,
  e2e: {
    
    baseUrl: process.env.baseUrl
    
  },
});
