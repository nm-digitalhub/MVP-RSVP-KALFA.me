const fs = require('fs');
const path = require('path');
const { chromium, devices } = require('/tmp/pwtmp/node_modules/playwright-core');

const baseUrl = process.env.CAPTURE_BASE_URL ?? 'https://kalfa.me';
const loginPath = process.env.CAPTURE_LOGIN_PATH ?? '/login';
const productPath = process.env.CAPTURE_PRODUCT_PATH ?? '/system/products/1';
const email = process.env.CAPTURE_EMAIL ?? 'netanel.kalfa@kalfa.me';
const password = process.env.CAPTURE_PASSWORD ?? 'test1234';
const mode = process.env.CAPTURE_MODE ?? 'fullpage';
const captureDevices = (process.env.CAPTURE_DEVICES ?? 'mobile').split(',').map((value) => value.trim()).filter(Boolean);
const outputDir = process.env.CAPTURE_OUTPUT_DIR ?? path.join(process.cwd(), 'public/system-products/capture-audit');
const disableSticky = process.env.CAPTURE_DISABLE_STICKY === '1';
const expandTree = process.env.CAPTURE_EXPAND_TREE !== '0';
const executablePath = process.env.CAPTURE_CHROMIUM_PATH ?? '/usr/bin/chromium-browser';

const sectionTargets = [
    { name: 'hero', selector: '[data-audit-section="product-hero"]' },
    { name: 'overview-stats', selector: '[data-audit-section="overview-stats"]' },
    { name: 'commercial-layer', selector: '[data-audit-section="commercial-layer"]' },
    { name: 'product-structure-toolbar', selector: '[data-audit-section="product-structure-toolbar"]' },
    { name: 'plans', selector: '#tree-plans-pricing' },
    { name: 'limits', selector: '#tree-limits' },
    { name: 'features', selector: '#tree-features' },
    { name: 'entitlements-tree', selector: '#tree-entitlements' },
    { name: 'catalog-entitlements', selector: '[data-audit-section="catalog-entitlements"]' },
];

const devicePresets = {
    desktop: {
        viewport: { width: 1600, height: 1200 },
        deviceScaleFactor: 1,
        locale: 'he-IL',
    },
    mobile: {
        ...devices['iPhone 14 Pro'],
        locale: 'he-IL',
    },
};

fs.mkdirSync(outputDir, { recursive: true });

function fileName(device, captureName) {
    return path.join(outputDir, `${device}-${captureName}.png`);
}

async function waitForStableLayout(page) {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });
    });
}

async function maybeDisableStickyChrome(page) {
    if (!disableSticky) {
        return;
    }

    await page.evaluate(() => {
        document.querySelectorAll('*').forEach((element) => {
            const styles = window.getComputedStyle(element);

            if (styles.position === 'fixed' || styles.position === 'sticky') {
                element.style.position = 'static';
                element.style.top = 'auto';
                element.style.bottom = 'auto';
            }
        });
    });
}

async function login(page) {
    await page.goto(`${baseUrl}${loginPath}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await waitForStableLayout(page);
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);

    await Promise.all([
        page.waitForURL('**/system/**', { timeout: 30000 }).catch(() => {}),
        page.locator('#submitBtn').click(),
    ]);

    await waitForStableLayout(page);
}

async function goToProductPage(page) {
    await page.goto(`${baseUrl}${productPath}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await waitForStableLayout(page);
    await maybeDisableStickyChrome(page);
}

async function expandTreeSections(page) {
    if (!expandTree) {
        return;
    }

    const expandAllButton = page.getByRole('button', { name: /expand all/i }).first();

    if (await expandAllButton.count()) {
        await expandAllButton.click();
        await waitForStableLayout(page);
    }

    for (const selector of ['#tree-plans-pricing', '#tree-limits', '#tree-features', '#tree-entitlements']) {
        const branch = page.locator(selector).first();

        if (!await branch.count()) {
            continue;
        }

        const items = page.locator(`${selector}-items`).first();

        if (await items.count() && !await items.isVisible()) {
            await branch.locator('button').first().click();
            await waitForStableLayout(page);
        }
    }
}

async function captureOverview(page, device) {
    await page.screenshot({
        path: fileName(device, 'overview'),
        fullPage: false,
    });
}

async function captureSections(page, device) {
    for (const target of sectionTargets) {
        const locator = page.locator(target.selector).first();

        if (!await locator.count()) {
            continue;
        }

        await locator.scrollIntoViewIfNeeded();
        await waitForStableLayout(page);
        await locator.screenshot({ path: fileName(device, target.name) });
    }
}

async function captureStops(page, device) {
    const customStops = process.env.CAPTURE_STOPS;
    const stops = customStops
        ? customStops.split(',').map((value) => Number.parseInt(value.trim(), 10)).filter((value) => Number.isFinite(value))
        : await page.evaluate(() => {
            const viewportHeight = window.innerHeight;
            const maxScroll = Math.max(document.documentElement.scrollHeight - viewportHeight, 0);

            if (maxScroll === 0) {
                return [0];
            }

            return [0, 0.25, 0.5, 0.75, 1].map((ratio) => Math.round(maxScroll * ratio));
        });

    for (const [index, stop] of stops.entries()) {
        await page.evaluate((scrollY) => {
            window.scrollTo(0, scrollY);
        }, stop);
        await page.waitForTimeout(250);
        await waitForStableLayout(page);
        await page.screenshot({
            path: fileName(device, `stop-${String(index + 1).padStart(2, '0')}`),
            fullPage: false,
        });
    }
}

async function captureFullPage(page, device) {
    await page.screenshot({
        path: fileName(device, 'fullpage'),
        fullPage: true,
    });
}

async function runForDevice(browser, device) {
    const useConfig = devicePresets[device];

    if (!useConfig) {
        throw new Error(`Unsupported CAPTURE_DEVICES value: ${device}`);
    }

    const context = await browser.newContext(useConfig);
    const page = await context.newPage();

    await login(page);
    await goToProductPage(page);
    await expandTreeSections(page);
    await captureOverview(page, device);

    if (mode === 'viewport') {
        await context.close();
        return;
    }

    if (mode === 'sections') {
        await captureSections(page, device);
        await context.close();
        return;
    }

    if (mode === 'stops') {
        await captureStops(page, device);
        await context.close();
        return;
    }

    if (mode === 'fullpage') {
        await captureFullPage(page, device);
        await context.close();
        return;
    }

    await context.close();
    throw new Error(`Unsupported CAPTURE_MODE value: ${mode}`);
}

async function main() {
    const browser = await chromium.launch({
        executablePath,
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    try {
        for (const device of captureDevices) {
            await runForDevice(browser, device);
        }

        console.log(JSON.stringify({
            ok: true,
            mode,
            devices: captureDevices,
            outputDir,
        }, null, 2));
    } finally {
        await browser.close();
    }
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
