import { type Page } from '@playwright/test';
import { BASE_URL } from '../simulation.config';

export class DealsPage {
    constructor(public readonly page: Page) {}

    async goto() {
        await this.page.goto(BASE_URL + '/deals');
        await this.page.waitForLoadState('networkidle');
    }

    async waitForList() {
        await this.page.waitForTimeout(2000);
    }

    async viewFirstDeal() {
        const link = this.page.locator('a[href*="deals/"]').first();
        await link.click();
        await this.page.waitForLoadState('networkidle');
    }
}
