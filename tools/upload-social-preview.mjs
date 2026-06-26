#!/usr/bin/env node
/**
 * Загрузка social preview репозитория GitHub (Settings → Social preview).
 * GitHub не предоставляет публичный REST API; используется UI через Playwright + PAT.
 *
 * Usage: GH_TOKEN=$(gh auth token) node tools/upload-social-preview.mjs [owner/repo] [image-path]
 */
import { chromium } from 'playwright';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const repo = process.argv[2] ?? 'cloudcastle-apps/di';
const imagePath = resolve(process.argv[3] ?? 'assets/social-preview.png');
const token = process.env.GH_TOKEN ?? process.env.GITHUB_TOKEN;

if (!token) {
  console.error('GH_TOKEN or GITHUB_TOKEN required');
  process.exit(1);
}

if (!existsSync(imagePath)) {
  console.error(`Image not found: ${imagePath}`);
  process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  extraHTTPHeaders: {
    Authorization: `Bearer ${token}`,
  },
});
const page = await context.newPage();

try {
  const settingsUrl = `https://github.com/${repo}/settings`;
  console.log(`Opening ${settingsUrl} ...`);
  await page.goto(settingsUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

  if (page.url().includes('/login')) {
    throw new Error('Not authenticated — cannot access settings UI');
  }

  await page.waitForTimeout(2000);

  const socialHeading = page.getByRole('heading', { name: /Social preview|Социальное изображение/i });
  if (await socialHeading.count()) {
    await socialHeading.first().scrollIntoViewIfNeeded();
  }

  const edit = page.locator('summary').filter({ hasText: /^(Edit|Изменить)$/ });
  if (await edit.count()) {
    await edit.first().click({ timeout: 15000 });
  }

  const uploadLabel = page.locator('label[for="repo-image-file-input"], button, a, span').filter({
    hasText: /Upload an image|Загрузить изображение/i,
  });
  if (await uploadLabel.count()) {
    await uploadLabel.first().click({ timeout: 10000 }).catch(() => {});
  }

  const fileInput = page.locator('#repo-image-file-input, input[type="file"][accept*="image"]').first();
  await fileInput.waitFor({ state: 'attached', timeout: 15000 });
  await fileInput.setInputFiles(imagePath);

  await page.waitForFunction(
    () => {
      const el = document.querySelector('file-attachment.js-upload-repository-image');
      return el && !el.classList.contains('is-default');
    },
    { timeout: 30000 },
  );

  console.log('Social preview uploaded.');

  await page.goto(`https://github.com/${repo}`, { waitUntil: 'domcontentloaded' });
  const og = await page.locator('meta[property="og:image"]').getAttribute('content');
  console.log('og:image:', og ?? '(not found yet)');
} finally {
  await browser.close();
}
