import { BaseUser } from './base-user';
import { BASE_URL } from '../simulation.config';
import { type PageName } from '../pages/dashboard-page';

const ALL_CRM_PAGES: PageName[] = [
    'dashboard',
    'deals',
    'contacts',
    'companies',
    'envelopes',
    'email-designer',
    'users',
    'teams',
    'roles',
    'permissions',
    'gdpr',
    'help',
    'request-data',
    'settings-profile',
    'settings-appearance',
    'settings-data',
];

export class SalesUser extends BaseUser {
    async runWorkflow(): Promise<void> {
        await this.login();
        await this.dashboard!.visitPages(ALL_CRM_PAGES);
        await this.screenshot('complete');
    }
}

export class ManagerUser extends BaseUser {
    async runWorkflow(): Promise<void> {
        await this.login();
        await this.dashboard!.visitPages(ALL_CRM_PAGES);
        await this.screenshot('complete');
    }
}

export class AdminUser extends BaseUser {
    async runWorkflow(): Promise<void> {
        await this.login();
        await this.dashboard!.visitPages(ALL_CRM_PAGES);
        await this.screenshot('complete');
    }
}

export class GuestUser extends BaseUser {
    async runWorkflow(): Promise<void> {
        const page = this.context!.pages()[0];
        await page.goto(BASE_URL + '/');
        await page.waitForLoadState('networkidle');
        await page.goto(BASE_URL + '/login');
        await page.waitForLoadState('networkidle');
        await this.screenshot('complete');
    }
}
