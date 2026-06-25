import 'dotenv/config';
import { defineConfig } from 'cypress';

export default defineConfig({
    viewportWidth: 1920,
    viewportHeight: 1080,
    allowCypressEnv: true,
    e2e: {
        video: false,
        numTestsKeptInMemory: 10,
        experimentalMemoryManagement: true,
        baseUrl: process.env.APP_URL,
    },
});
