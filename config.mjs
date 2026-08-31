// Local-only configuration.
// Set BLUEPRINT_ADMIN_PASSWORD in your shell or .env/local environment.
const password = process.env.BLUEPRINT_ADMIN_PASSWORD;

if (!password) {
  throw new Error('BLUEPRINT_ADMIN_PASSWORD is not configured. Set it before starting the editor.');
}

export const adminPassword = password;
