import { chromium, type Browser, type BrowserContext } from '@playwright/test';
import { type UserConfig } from '../simulation.config';
import { AuthPage } from '../pages/auth-page';
import { DashboardPage } from '../pages/dashboard-page';

export abstract class BaseUser {
    protected context!: BrowserContext;
    protected auth: AuthPage | null = null;
    protected dashboard: DashboardPage | null = null;
    protected errors: Error[] = [];

    constructor(
        protected config: UserConfig,
        protected browser: Browser,
    ) {}

    async initialize(): Promise<void> {
        this.context = await this.browser.newContext({
            viewport: { width: 1280, height: 720 },
        });

        const page = await this.context.newPage();
        this.auth = new AuthPage(page);
        this.dashboard = new DashboardPage(page);
    }

    abstract runWorkflow(): Promise<void>;

    async cleanup(): Promise<void> {
        await this.context?.close();
    }

    protected async login(): Promise<void> {
        if (!this.auth) throw new Error('Not initialized');
        await this.auth.goto();
        await this.auth.login(this.config.email, this.config.password);
    }

    protected async screenshot(name: string): Promise<void> {
        const page = this.context?.pages()[0];
        if (page) {
            await page.screenshot({
                path: `tests/BrowserSimulation/screenshots/user${this.config.id}-${name}.png`,
            });
        }
    }

    hasErrors(): boolean {
        return this.errors.length > 0;
    }

    getErrors(): Error[] {
        return this.errors;
    }
}
