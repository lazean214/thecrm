import { type Page } from '@playwright/test';
import { BASE_URL } from '../simulation.config';

const ROUTES = {
    dashboard: '/dashboard',
    deals: '/deals',
    contacts: '/contacts',
    companies: '/companies',
    envelopes: '/envelopes',
    'email-designer': '/designer',
    users: '/users',
    teams: '/teams',
    roles: '/roles',
    permissions: '/permissions',
    gdpr: '/admin/gdpr',
    help: '/help',
    'request-data': '/gdpr/export',
    'settings-profile': '/settings/profile',
    'settings-appearance': '/settings/appearance',
    'settings-data': '/settings/data',
} as const;

export type PageName = keyof typeof ROUTES;

export class DashboardPage {
    constructor(public readonly page: Page) {}

    async waitForLoad() {
        await this.page.waitForURL('**/dashboard', { timeout: 10000 });
    }

    async goto(pageName: PageName) {
        const path = ROUTES[pageName];
        if (!path) throw new Error(`Unknown page: ${pageName}`);
        await this.page.goto(BASE_URL + path);
        await this.page.waitForLoadState('networkidle');
    }

    async visitPages(pages: PageName[]) {
        for (const pageName of pages) {
            await this.goto(pageName);
        }
    }

    async logout() {
        await this.page.goto(BASE_URL + '/');
        await this.page.evaluate(() => {
            const form = document.querySelector('form[action*="logout"]');
            if (form) (form as HTMLFormElement).submit();
        });
    }
}
