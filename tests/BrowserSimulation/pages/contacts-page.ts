import { type Page } from '@playwright/test';
import { BASE_URL } from '../simulation.config';

export class ContactsPage {
    constructor(public readonly page: Page) {}

    async goto() {
        await this.page.goto(BASE_URL + '/contacts');
        await this.page.waitForLoadState('networkidle');
    }

    async waitForList() {
        await this.page.waitForSelector('a[href*="contacts/"]', { timeout: 10000 }).catch(() => {});
    }

    async viewFirstContact() {
        const link = this.page.locator('a[href*="contacts/"]').first();
        await link.click();
        await this.page.waitForLoadState('networkidle');
    }
}
