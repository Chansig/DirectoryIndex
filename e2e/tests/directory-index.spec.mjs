import { expect, test } from '@playwright/test';

const applicationHostname = new URL(process.env.PLAYWRIGHT_BASE_URL ?? 'http://web:8080').hostname;

test('renders the fixture index and security headers', async ({ page }) => {
  const response = await page.goto('/');

  expect(response?.status()).toBe(200);
  expect(response?.headers()['content-security-policy']).toContain("default-src 'none'");
  expect(response?.headers()['x-content-type-options']).toBe('nosniff');
  await expect(page).toHaveTitle('Index of /');
  await expect(page.getByRole('link', { name: 'documents/' })).toBeVisible();
  await expect(page.getByText('.env')).toHaveCount(0);
  await expect(page.getByText('outside-link')).toHaveCount(0);
});

test('navigates into a directory and back to its parent', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'documents/' }).click();

  await expect(page).toHaveURL(/path=documents/);
  await expect(page.getByRole('heading', { name: 'Index of /documents' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Parent directory' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'rapport été 2026.txt' })).toBeVisible();

  await page.getByRole('link', { name: 'Parent directory' }).click();
  await expect(page).toHaveURL(/\/index\.php$/);
});

test('navigates through nested directories', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'archives/' }).click();
  await expect(page.getByRole('heading', { name: 'Index of /archives' })).toBeVisible();

  await page.getByRole('link', { name: '2025/' }).click();
  await expect(page).toHaveURL(/path=archives(?:%2F|\/)2025/i);
  await expect(page.getByRole('heading', { name: 'Index of /archives/2025' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'summary.csv' })).toBeVisible();

  await page.getByRole('link', { name: 'Parent directory' }).click();
  await expect(page.getByRole('heading', { name: 'Index of /archives' })).toBeVisible();
});

test('sorts entries without making external requests', async ({ page }, testInfo) => {
  const externalRequests = [];
  page.on('request', request => {
    if (new URL(request.url()).hostname !== applicationHostname) {
      externalRequests.push(request.url());
    }
  });

  await page.goto('/');
  if (testInfo.project.name === 'chromium-mobile') {
    await page.goto('/index.php?sort=ascsize');
  } else {
    await page.getByRole('link', { name: 'Size' }).click();
  }
  await expect(page).toHaveURL(/sort=ascsize/);
  expect(externalRequests).toEqual([]);
});

test('matches the visual reference', async ({ page }, testInfo) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot(`root-${testInfo.project.name}.png`, {
    animations: 'disabled',
    fullPage: true,
  });
});
