import { chromium, type Browser } from '@playwright/test';
import { userPersonas, type UserConfig } from './simulation.config';
import { SalesUser, ManagerUser, AdminUser, GuestUser } from './users/user-workflows';
import { BaseUser } from './users/base-user';

const SCREENSHOT_DIR = 'tests/BrowserSimulation/screenshots';

interface UserResult {
    id: number;
    persona: string;
    name: string;
    success: boolean;
    error?: string;
    durationMs: number;
}

function createUser(config: UserConfig, browser: Browser): BaseUser {
    switch (config.persona) {
        case 'sales': return new SalesUser(config, browser);
        case 'manager': return new ManagerUser(config, browser);
        case 'admin': return new AdminUser(config, browser);
        case 'guest': return new GuestUser(config, browser);
    }
}

function formatDuration(ms: number): string {
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(1)}s`;
}

function printStatusBar(results: UserResult[]): void {
    const total = results.length;
    const passed = results.filter(r => r.success).length;
    const failed = results.filter(r => !r.success).length;
    const barWidth = 40;
    const passedWidth = Math.round((passed / total) * barWidth);
    const failedWidth = barWidth - passedWidth;

    const bar = '█'.repeat(passedWidth) + '░'.repeat(failedWidth);
    process.stdout.write(`\r  ${bar} ${passed}/${total} passed, ${failed} failed`);
}

function printSummary(results: UserResult[]): void {
    const passed = results.filter(r => r.success);
    const failed = results.filter(r => !r.success);

    console.log('\n');
    console.log('═'.repeat(60));
    console.log('  SIMULATION SUMMARY');
    console.log('═'.repeat(60));
    console.log(`  Total: ${results.length} users`);
    console.log(`  Passed: ${passed.length}`);
    console.log(`  Failed: ${failed.length}`);

    if (failed.length > 0) {
        console.log('\n  Failures:');
        for (const r of failed) {
            console.log(`    • User ${r.id} (${r.persona}): ${r.error}`);
        }
    }

    const durations = results.map(r => r.durationMs);
    const avg = durations.reduce((a, b) => a + b, 0) / durations.length;
    console.log(`\n  Avg duration: ${formatDuration(avg)}`);
    console.log(`  Total time: ${formatDuration(Math.max(...durations))}`);
    console.log('═'.repeat(60));
}

async function main(): Promise<void> {
    console.log('═'.repeat(60));
    console.log('  BROWSER USER SIMULATION');
    console.log('  Spawning 20 concurrent browser windows...');
    console.log('═'.repeat(60));
    console.log('');

    const headless = process.env.HEADLESS === 'true';
    const browser = await chromium.launch({ headless });
    const results: UserResult[] = [];

    try {

        const users = userPersonas.map(c => createUser(c, browser));
        console.log(`  Initializing ${users.length} users...`);

        for (const user of users) {
            await user.initialize();
        }

        console.log(`  Running ${users.length} user workflows...\n`);

        const running = users.map(async (user, i) => {
            const start = performance.now();
            try {
                await user.runWorkflow();
                const durationMs = Math.round(performance.now() - start);
                results.push({
                    id: user['config'].id,
                    persona: user['config'].persona,
                    name: user['config'].name,
                    success: true,
                    durationMs,
                });
            } catch (error) {
                const durationMs = Math.round(performance.now() - start);
                await user['screenshot']('error').catch(() => {});
                results.push({
                    id: user['config'].id,
                    persona: user['config'].persona,
                    name: user['config'].name,
                    success: false,
                    error: error instanceof Error ? error.message : String(error),
                    durationMs,
                });
            }
            printStatusBar(results);
        });

        await Promise.all(running);
        console.log('');

        for (const user of users) {
            await user.cleanup();
        }

        printSummary(results);

    } finally {
        await browser.close();
    }

    const failedCount = results.filter(r => !r.success).length;
    process.exit(failedCount > 0 ? 1 : 0);
}

main();
