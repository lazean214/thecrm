import { type Page } from '@playwright/test';
import { BASE_URL } from '../simulation.config';

export class AuthPage {
    constructor(public readonly page: Page) {}

    async goto() {
        await this.page.goto(BASE_URL + '/login');
        await this.page.waitForSelector('input[name="email"]');
    }

    async login(email: string, password: string) {
        await this.page.fill('input[name="email"]', email);
        await this.page.fill('input[name="password"]', password);
        await this.page.click('button[data-test="login-button"]');
        await this.page.waitForURL(/\/dashboard/, { timeout: 10000 });
    }

    async isLoggedIn(): Promise<boolean> {
        return this.page.url().includes('/dashboard');
    }
}
