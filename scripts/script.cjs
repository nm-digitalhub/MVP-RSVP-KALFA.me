
const fs = require('fs');
const path = require('path');
const { chromium, devices } = require('/tmp/pwtmp/node_modules/playwright-core');

const baseUrl = process.env.CAPTURE_BASE_URL ?? 'https://kalfa.me';
const loginPath = process.env.CAPTURE_LOGIN_PATH ?? '/login';
const email = process.env.CAPTURE_EMAIL ?? '';
const password = process.env.CAPTURE_PASSWORD ?? '';
const mode = process.env.CAPTURE_MODE ?? 'fullpage';
/**
 * CAPTURE_DEVICES: מופרד בפסיקים.
 * מילות מפתח: desktop, mobile (mobile משתמש ב-CAPTURE_MOBILE_DEVICE).
 * בנוסף: כל שם מכשיר מ-playwright-core/devices, למשל "iPhone 15 Pro Max", "Galaxy S24".
 */
const captureDevices = (process.env.CAPTURE_DEVICES ?? 'mobile,desktop')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean);
const outputDir = process.env.CAPTURE_OUTPUT_DIR ?? path.join(process.cwd(), 'public/system-products/capture-audit');
const disableSticky = process.env.CAPTURE_DISABLE_STICKY === '1';
const expandTree = process.env.CAPTURE_EXPAND_TREE !== '0';
const executablePath = process.env.CAPTURE_CHROMIUM_PATH ?? '/usr/bin/chromium-browser';

const orgId = process.env.CAPTURE_SYSTEM_ORG_ID ?? '1';
const accountId = process.env.CAPTURE_SYSTEM_ACCOUNT_ID ?? '1';
const userId = process.env.CAPTURE_SYSTEM_USER_ID ?? '1';

function resolveProductId() {
    if (process.env.CAPTURE_PRODUCT_ID) {
        return process.env.CAPTURE_PRODUCT_ID;
    }

    const productPathEnv = process.env.CAPTURE_PRODUCT_PATH;

    if (productPathEnv) {
        const match = productPathEnv.match(/\/products\/(\d+)/);

        if (match) {
            return match[1];
        }
    }

    return '1';
}

const productId = resolveProductId();

/**
 * נתיבי GET מ-routes/web.php (קבוצת system.admin) — רק אחרי התחברות.
 */
function defaultAuthCapturePaths() {
    return [
        '/system/dashboard',
        '/system/settings',
        '/system/organizations',
        `/system/organizations/${orgId}`,
        '/system/users',
        `/system/users/${userId}`,
        '/system/accounts',
        '/system/accounts/create',
        `/system/accounts/${accountId}`,
        '/system/products',
        '/system/products/create',
        `/system/products/${productId}`,
        '/system/trial-reminders',
    ];
}

function resolveCapturePaths() {
    const raw = process.env.CAPTURE_PAGE_PATHS?.trim();

    if (raw) {
        return raw.split(',').map((p) => p.trim()).filter(Boolean);
    }

    return defaultAuthCapturePaths();
}

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

const mobileDeviceName = process.env.CAPTURE_MOBILE_DEVICE ?? 'iPhone 14 Pro';
const mobileDsf = Number.parseFloat(process.env.CAPTURE_MOBILE_SCALE ?? '', 10);

fs.mkdirSync(outputDir, { recursive: true });

function desktopContextOptions() {
    return {
        viewport: {
            width: Number.parseInt(process.env.CAPTURE_DESKTOP_WIDTH ?? '1600', 10),
            height: Number.parseInt(process.env.CAPTURE_DESKTOP_HEIGHT ?? '1200', 10),
        },
        deviceScaleFactor: Number.parseFloat(process.env.CAPTURE_DESKTOP_SCALE ?? '1', 10),
        locale: 'he-IL',
    };
}

/** שם קובץ בטוח מתוך מפתח מכשיר (למשל "iPhone 15 Pro" → iphone-15-pro). */
function deviceFileSlug(deviceKey) {
    const trimmed = String(deviceKey).trim();
    const lower = trimmed.toLowerCase();

    if (lower === 'desktop' || lower === 'mobile') {
        return lower;
    }

    const slug = lower
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug || 'device';
}

function resolveBrowserContextOptions(deviceKey) {
    const key = String(deviceKey).trim();
    const lower = key.toLowerCase();

    if (lower === 'desktop') {
        return desktopContextOptions();
    }

    if (lower === 'mobile') {
        const preset = devices[mobileDeviceName] ?? devices['iPhone 14 Pro'];

        return {
            ...preset,
            locale: 'he-IL',
            ...(Number.isFinite(mobileDsf) ? { deviceScaleFactor: mobileDsf } : {}),
        };
    }

    const fromPlaywright = devices[key];

    if (fromPlaywright) {
        return {
            ...fromPlaywright,
            locale: 'he-IL',
        };
    }

    throw new Error(
        `Unknown CAPTURE_DEVICES entry "${key}". Use desktop, mobile, or a Playwright device name (e.g. iPhone 15 Pro Max, Galaxy S24, Galaxy A55).`,
    );
}

function pathToSlug(urlPath) {
    const normalized = urlPath.replace(/^\/+|\/+$/g, '');

    if (!normalized) {
        return 'root';
    }

    return normalized.replace(/\//g, '-');
}

function fileName(device, pageSlug, captureName) {
    return path.join(outputDir, `${device}-${pageSlug}-${captureName}.png`);
}

function isSystemProductDetailPath(urlPath) {
    return /^\/system\/products\/\d+$/.test(urlPath);
}

async function waitForStableLayout(page) {
    await page.waitForLoadState('networkidle').catch(() => {});

    const settleMs = Number.parseInt(process.env.CAPTURE_LAYOUT_SETTLE_MS ?? '350', 10);

    if (settleMs > 0) {
        await page.waitForTimeout(settleMs);
    }

    await page.evaluate(async () => {
        if (document.fonts && document.fonts.ready) {
            try {
                await document.fonts.ready;
            } catch (_) {
                /* ignore */
            }
        }

        await new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });
    });
}

/**
 * במעטפת enterprise-app הגלילה היא על #main-content — fullPage של document חותך את התוכן.
 * מחזירים לראש הגלילה לפני כל צילום.
 */
async function resetScrollPositions(page) {
    await page.evaluate(() => {
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        const mainContent = document.querySelector('#main-content');

        if (mainContent) {
            mainContent.scrollTop = 0;
        }
    });
}

async function screenshotFullScrollable(page, outPath) {
    const screenshotOpts = {
        path: outPath,
        fullPage: true,
        animations: 'disabled',
    };

    const mainContent = page.locator('#main-content').first();

    if (await mainContent.count()) {
        const innerScrolls = await mainContent.evaluate((el) => el.scrollHeight > el.clientHeight + 2);

        if (innerScrolls) {
            try {
                await mainContent.screenshot(screenshotOpts);

                return;
            } catch (err) {
                console.error('[capture] #main-content fullPage failed, falling back to page:', err.message ?? err);
            }
        }
    }

    await page.screenshot(screenshotOpts);
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
    if (!email || !password) {
        throw new Error('Set CAPTURE_EMAIL and CAPTURE_PASSWORD (e.g. CAPTURE_EMAIL=x CAPTURE_PASSWORD=y node scripts/script.cjs)');
    }

    await page.goto(`${baseUrl}${loginPath}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await waitForStableLayout(page);
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);

    await Promise.all([
        page.waitForURL((u) => !u.pathname.endsWith('/login') && u.pathname !== loginPath, { timeout: 45000 }).catch(() => {}),
        page.locator('#submitBtn').click(),
    ]);

    await waitForStableLayout(page);
}

async function gotoCapturePath(page, urlPath) {
    const target = `${baseUrl}${urlPath.startsWith('/') ? urlPath : `/${urlPath}`}`;
    await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 60000 });
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

async function captureOverview(page, device, pageSlug) {
    await resetScrollPositions(page);
    await waitForStableLayout(page);
    await page.screenshot({
        path: fileName(device, pageSlug, 'overview'),
        fullPage: false,
        animations: 'disabled',
    });
}

async function captureSections(page, device, pageSlug) {
    for (const target of sectionTargets) {
        const locator = page.locator(target.selector).first();

        if (!await locator.count()) {
            continue;
        }

        await locator.scrollIntoViewIfNeeded();
        await waitForStableLayout(page);
        await locator.screenshot({ path: fileName(device, pageSlug, target.name) });
    }
}

async function captureStops(page, device, pageSlug) {
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
            path: fileName(device, pageSlug, `stop-${String(index + 1).padStart(2, '0')}`),
            fullPage: false,
        });
    }
}

async function captureFullPage(page, device, pageSlug) {
    await resetScrollPositions(page);
    await waitForStableLayout(page);
    await screenshotFullScrollable(page, fileName(device, pageSlug, 'fullpage'));
}

async function capturePage(page, deviceSlug, urlPath) {
    const pageSlug = pathToSlug(urlPath);

    await gotoCapturePath(page, urlPath);

    if (isSystemProductDetailPath(urlPath)) {
        await expandTreeSections(page);
    }

    await resetScrollPositions(page);
    await waitForStableLayout(page);

    if (mode === 'viewport') {
        await captureOverview(page, deviceSlug, pageSlug);

        return;
    }

    if (mode === 'sections') {
        await captureOverview(page, deviceSlug, pageSlug);
        await captureSections(page, deviceSlug, pageSlug);

        return;
    }

    if (mode === 'stops') {
        await captureStops(page, deviceSlug, pageSlug);

        return;
    }

    if (mode === 'fullpage') {
        await captureFullPage(page, deviceSlug, pageSlug);

        return;
    }

    throw new Error(`Unsupported CAPTURE_MODE value: ${mode}`);
}

async function runForDevice(browser, deviceKey) {
    const useConfig = resolveBrowserContextOptions(deviceKey);
    const deviceSlug = deviceFileSlug(deviceKey);
    const paths = resolveCapturePaths();
    const context = await browser.newContext(useConfig);
    const page = await context.newPage();

    await login(page);

    for (const urlPath of paths) {
        try {
            await capturePage(page, deviceSlug, urlPath);
            console.error(`[capture] ok ${deviceKey} → ${urlPath}`);
        } catch (err) {
            console.error(`[capture] fail ${deviceKey} ${urlPath}:`, err.message ?? err);
        }
    }

    await context.close();
}

async function main() {
    const browser = await chromium.launch({
        executablePath,
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    try {
        const paths = resolveCapturePaths();

        for (const device of captureDevices) {
            await runForDevice(browser, device);
        }

        console.log(JSON.stringify({
            ok: true,
            mode,
            devices: captureDevices,
            deviceSlugs: captureDevices.map((k) => ({ key: k, fileSlug: deviceFileSlug(k) })),
            paths,
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
