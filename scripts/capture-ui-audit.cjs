const fs = require('fs');
const path = require('path');
const { chromium, devices } = require('/tmp/pwtmp/node_modules/playwright-core');

const baseUrl = process.env.CAPTURE_BASE_URL ?? 'https://kalfa.me';
const loginPath = process.env.CAPTURE_LOGIN_PATH ?? '/login';
const email = process.env.CAPTURE_EMAIL ?? 'netanel.kalfa@kalfa.me';
const password = process.env.CAPTURE_PASSWORD ?? 'test1234';
const executablePath = process.env.CAPTURE_CHROMIUM_PATH ?? '/usr/bin/chromium-browser';

const outputDir =
    process.env.CAPTURE_OUTPUT_DIR ??
    path.join(process.cwd(), 'public/ui-audit-captures');

const disableSticky = process.env.CAPTURE_DISABLE_STICKY === '1';
const captureDevices = (process.env.CAPTURE_DEVICES ?? 'desktop,mobile')
    .split(',')
    .map((v) => v.trim())
    .filter(Boolean);

const auditRoutes = [
    {
        key: 'dashboard',
        path: process.env.CAPTURE_DASHBOARD_PATH ?? '/dashboard',
        sections: [
            { name: 'kpi-strip', selector: '[data-audit-section="kpi-strip"]' },
            { name: 'primary-table', selector: '[data-audit-section="primary-table"]' },
            { name: 'quick-actions', selector: '[data-audit-section="quick-actions"]' },
        ],
    },
    {
        key: 'events-index',
        path: process.env.CAPTURE_EVENTS_PATH ?? '/dashboard/events',
        sections: [
            { name: 'events-toolbar', selector: '[data-audit-section="events-toolbar"]' },
            { name: 'events-table', selector: '[data-audit-section="events-table"]' },
        ],
    },
    {
        key: 'system-products-show',
        path: process.env.CAPTURE_PRODUCT_PATH ?? '/system/products/1',
        sections: [
            { name: 'hero', selector: '[data-audit-section="product-hero"]' },
            { name: 'overview-stats', selector: '[data-audit-section="overview-stats"]' },
            { name: 'commercial-layer', selector: '[data-audit-section="commercial-layer"]' },
            { name: 'product-structure-toolbar', selector: '[data-audit-section="product-structure-toolbar"]' },
            { name: 'plans', selector: '#tree-plans-pricing' },
            { name: 'limits', selector: '#tree-limits' },
            { name: 'features', selector: '#tree-features' },
            { name: 'entitlements-tree', selector: '#tree-entitlements' },
            { name: 'catalog-entitlements', selector: '[data-audit-section="catalog-entitlements"]' },
        ],
    },
];

const devicePresets = {
    desktop: {
        viewport: { width: 1600, height: 1200 },
        deviceScaleFactor: 1,
        locale: 'he-IL',
    },
    tablet: {
        ...devices['iPad Pro 11'],
        locale: 'he-IL',
    },
    mobile: {
        ...devices['iPhone 14 Pro'],
        locale: 'he-IL',
    },
};

fs.mkdirSync(outputDir, { recursive: true });

function sanitize(value) {
    return String(value).replace(/[^a-z0-9-_]/gi, '-').toLowerCase();
}

function outFile(device, routeKey, name) {
    return path.join(outputDir, `${sanitize(device)}-${sanitize(routeKey)}-${sanitize(name)}.png`);
}

function outJson(name) {
    return path.join(outputDir, `${sanitize(name)}.json`);
}

async function waitForStableLayout(page) {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(250);

    await page.evaluate(async () => {
        if (document.fonts?.ready) {
            try {
                await document.fonts.ready;
            } catch (_) {}
        }

        await new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });
    });
}

async function maybeDisableStickyChrome(page) {
    if (!disableSticky) return;

    await page.evaluate(() => {
        document.querySelectorAll('*').forEach((el) => {
            const s = window.getComputedStyle(el);
            if (s.position === 'sticky' || s.position === 'fixed') {
                el.style.position = 'static';
                el.style.top = 'auto';
                el.style.bottom = 'auto';
                el.style.left = 'auto';
                el.style.right = 'auto';
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

async function gotoPath(page, appPath) {
    await page.goto(`${baseUrl}${appPath}`, {
        waitUntil: 'domcontentloaded',
        timeout: 60000,
    });

    await waitForStableLayout(page);
    await maybeDisableStickyChrome(page);
}

async function expandTreeSections(page) {
    const expandAllButton = page.getByRole('button', { name: /expand all/i }).first();

    if (await expandAllButton.count()) {
        await expandAllButton.click().catch(() => {});
        await waitForStableLayout(page);
    }

    for (const selector of ['#tree-plans-pricing', '#tree-limits', '#tree-features', '#tree-entitlements']) {
        const branch = page.locator(selector).first();
        if (!await branch.count()) continue;

        const button = branch.locator('button').first();
        if (await button.count()) {
            const ariaExpanded = await button.getAttribute('aria-expanded').catch(() => null);
            if (ariaExpanded === 'false') {
                await button.click().catch(() => {});
                await waitForStableLayout(page);
            }
        }
    }
}

async function closeFloatingUi(page) {
    await page.keyboard.press('Escape').catch(() => {});
    await page.waitForTimeout(150);
}

async function openMobileDrawer(page) {
    const candidates = [
        '[data-mobile-nav-toggle]',
        '[aria-label*="menu" i]',
        '[aria-label*="navigation" i]',
        'button:has(svg)',
    ];

    for (const selector of candidates) {
        const locator = page.locator(selector).first();
        if (await locator.count()) {
            const box = await locator.boundingBox().catch(() => null);
            if (box && box.width > 20 && box.height > 20) {
                await locator.click().catch(() => {});
                await waitForStableLayout(page);
                return true;
            }
        }
    }

    return false;
}

async function captureViewport(page, device, routeKey, name) {
    await page.screenshot({
        path: outFile(device, routeKey, name),
        fullPage: false,
    });
}

async function captureFullPage(page, device, routeKey) {
    await page.screenshot({
        path: outFile(device, routeKey, 'fullpage'),
        fullPage: true,
    });
}

async function captureSections(page, device, routeKey, sections) {
    for (const section of sections) {
        const locator = page.locator(section.selector).first();
        if (!await locator.count()) continue;

        await locator.scrollIntoViewIfNeeded().catch(() => {});
        await waitForStableLayout(page);
        await locator.screenshot({
            path: outFile(device, routeKey, section.name),
        }).catch(() => {});
    }
}

async function captureScrollStops(page, device, routeKey) {
    const stops = await page.evaluate(() => {
        const viewportHeight = window.innerHeight;
        const maxScroll = Math.max(document.documentElement.scrollHeight - viewportHeight, 0);

        if (maxScroll <= 0) return [0];

        return [0, 0.25, 0.5, 0.75, 1].map((ratio) => Math.round(maxScroll * ratio));
    });

    for (const [index, stop] of stops.entries()) {
        await page.evaluate((scrollY) => window.scrollTo(0, scrollY), stop);
        await waitForStableLayout(page);
        await captureViewport(page, device, routeKey, `stop-${String(index + 1).padStart(2, '0')}`);
    }
}

async function collectMeta(page, device, routeKey) {
    const meta = await page.evaluate(() => {
        const html = document.documentElement;
        return {
            width: window.innerWidth,
            height: window.innerHeight,
            dir: html.getAttribute('dir'),
            lang: html.getAttribute('lang'),
            title: document.title,
            url: location.href,
        };
    });

    return {
        device,
        routeKey,
        ...meta,
    };
}

async function captureRoute(page, device, routeConfig) {
    await gotoPath(page, routeConfig.path);

    if (routeConfig.key.includes('product')) {
        await expandTreeSections(page);
    }

    await captureViewport(page, device, routeConfig.key, 'above-the-fold');
    await captureSections(page, device, routeConfig.key, routeConfig.sections ?? []);
    await captureScrollStops(page, device, routeConfig.key);
    await captureFullPage(page, device, routeConfig.key);

    if (device === 'mobile' || device === 'tablet') {
        await closeFloatingUi(page);
        const opened = await openMobileDrawer(page);

        if (opened) {
            await captureViewport(page, device, routeConfig.key, 'mobile-drawer-open');
            await captureFullPage(page, device, routeConfig.key, 'mobile-drawer-open-full').catch(() => {});
            await closeFloatingUi(page);
        }
    }

    return collectMeta(page, device, routeConfig.key);
}

async function runForDevice(browser, device) {
    const preset = devicePresets[device];

    if (!preset) {
        throw new Error(`Unsupported device: ${device}`);
    }

    const context = await browser.newContext(preset);
    const page = await context.newPage();

    const deviceMeta = [];

    try {
        await login(page);

        for (const routeConfig of auditRoutes) {
            const meta = await captureRoute(page, device, routeConfig);
            deviceMeta.push(meta);
        }
    } finally {
        await context.close();
    }

    return deviceMeta;
}

async function main() {
    const browser = await chromium.launch({
        executablePath,
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    const meta = [];

    try {
        for (const device of captureDevices) {
            const result = await runForDevice(browser, device);
            meta.push(...result);
        }

        fs.writeFileSync(outJson('capture-meta'), JSON.stringify({
            ok: true,
            capturedAt: new Date().toISOString(),
            baseUrl,
            outputDir,
            devices: captureDevices,
            routes: auditRoutes.map((r) => ({ key: r.key, path: r.path })),
            meta,
        }, null, 2));

        console.log(JSON.stringify({
            ok: true,
            outputDir,
            devices: captureDevices,
            routes: auditRoutes.map((r) => r.key),
        }, null, 2));
    } finally {
        await browser.close();
    }
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});