# Browser-Based User Simulation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a visible browser-based simulation system running 20 simultaneous users testing the CRM application via Playwright.

**Architecture:** Use Playwright's multi-context browser automation with a Node.js orchestrator that spawns 20 visible Chrome windows, each executing unique user workflows.

**Tech Stack:** Node.js, Playwright, TypeScript, Laravel (existing)

## Global Constraints

- Playwright for browser automation (not Puppeteer)
- Visible/non-headless browser windows
- 20 concurrent users
- Run on Laravel dev server (localhost:8000)
- TypeScript configuration files

---

## File Structure

| File | Responsibility |
|------|----------------|
| `playwright.config.ts` | Playwright base configuration with non-headless setting |
| `simulation.config.ts` | User persona definitions and workflow configurations |
| `orchestrator.ts` | Main runner that orchestrates all 20 browser contexts |
| `users/base-user.ts` | Base class with common user actions (login, navigate, logout) |
| `users/user-workflows.ts` | Persona-specific workflows (sales, manager, admin, guest) |
| `tests/BrowserSimulation/pages/dashboard-page.ts` | Page object for dashboard |
| `tests/BrowserSimulation/pages/contacts-page.ts` | Page object for contacts |
| `tests/BrowserSimulation/pages/deals-page.ts` | Page object for deals |
| `tests/BrowserSimulation/pages/auth-page.ts` | Page object for authentication |

---

## Task 1: Install Playwright and TypeScript Dependencies

**Files:**
- Create: `tests/BrowserSimulation/playwright.config.ts`

**Interfaces:**
- Consumes: None

**Steps:**
- [ ] **Step 1: Install Playwright**
```bash
npm install -D @playwright/test
npx playwright install chromium
```
Expected: chromium browser installed successfully

- [ ] **Step 2: Create playwright.config.ts**
```typescript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30000,
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 20,
  reporter: 'list',
  use: {
    baseURL: 'http://localhost:8000',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'php artisan serve',
    url: 'http://localhost:8000',
    reuseExistingServer: !process.env.CI,
  },
});
```

- [ ] **Step 3: Verify installation**
```bash
npx playwright test --list
```
Expected: Lists tests (none yet) without errors

- [ ] **Step 4: Commit**
```bash
git add tests/BrowserSimulation/playwright.config.ts package.json
git commit -m "feat: add Playwright configuration for browser simulation"
```

---

## Task 2: Create Page Object Models

**Files:**
- Create: `tests/BrowserSimulation/pages/auth-page.ts`

**Interfaces:**
- Consumes: None
- Produces: `AuthPage` class with `goto()`, `login(email, password)` methods

- [ ] **Step 1: Create auth-page.ts**
```typescript
import { type Page, expect } from '@playwright/test';

export class AuthPage {
  readonly page: Page;
  readonly emailInput = 'input[type="email"]';
  readonly passwordInput = 'input[type="password"]';
  readonly submitButton = 'button[type="submit"]';

  constructor(page: Page) {
    this.page = page;
  }

  async goto() {
    await this.page.goto('/login');
  }

  async login(email: string, password: string) {
    await this.page.fill(this.emailInput, email);
    await this.page.fill(this.passwordInput, password);
    await this.page.click(this.submitButton);
    await this.page.waitForURL('/dashboard');
  }
}
```

- [ ] **Step 2: Commit**
```bash
git add tests/BrowserSimulation/pages/auth-page.ts
git commit -m "feat: add AuthPage page object model"
```

---

## Task 3: Create Dashboard Page Object

**Files:**
- Create: `tests/BrowserSimulation/pages/dashboard-page.ts`

**Interfaces:**
- Produces: `DashboardPage` class with `waitForLoad()`, `navigateToContacts()`, `navigateToDeals()` methods

- [ ] **Step 1: Create dashboard-page.ts**
```typescript
import { type Page } from '@playwright/test';

export class DashboardPage {
  readonly page: Page;
  readonly contactsLink = 'a[href*="contacts"]';
  readonly dealsLink = 'a[href*="deals"]';
  readonly logoutButton = 'button[type="submit"]';

  constructor(page: Page) {
    this.page = page;
  }

  async waitForLoad() {
    await this.page.waitForSelector('h1:has-text("Dashboard")', { timeout: 5000 });
  }

  async navigateToContacts() {
    await this.page.click(this.contactsLink);
    await this.page.waitForURL(/contacts/);
  }

  async navigateToDeals() {
    await this.page.click(this.dealsLink);
    await this.page.waitForURL(/deals/);
  }
}
```

- [ ] **Step 2: Commit**
```bash
git add tests/BrowserSimulation/pages/dashboard-page.ts
git commit -m "feat: add DashboardPage page object model"
```

---

## Task 4: Create User Configuration

**Files:**
- Create: `tests/BrowserSimulation/simulation.config.ts`

**Interfaces:**
- Produces: `UserConfig` interface, `userPersonas` array with 20 users

- [ ] **Step 1: Create simulation.config.ts**
```typescript
export type UserPersona = 'sales' | 'manager' | 'admin' | 'guest';

export interface UserConfig {
  id: number;
  email: string;
  password: string;
  persona: UserPersona;
}

export const userPersonas: UserConfig[] = Array.from({ length: 20 }, (_, i) => ({
  id: i + 1,
  email: `user${i + 1}@test.com`,
  password: 'password',
  persona: i < 8 ? 'sales' : i < 14 ? 'manager' : i < 18 ? 'admin' : 'guest',
}));
```

- [ ] **Step 2: Commit**
```bash
git add tests/BrowserSimulation/simulation.config.ts
git commit -m "feat: add user persona configuration for 20-user simulation"
```

---

## Task 5: Create Base User Class

**Files:**
- Create: `tests/BrowserSimulation/users/base-user.ts`

**Interfaces:**
- Consumes: `AuthPage`, `DashboardPage`
- Produces: `BaseUser` class with `runWorkflow()` method

- [ ] **Step 1: Create base-user.ts**
```typescript
import { type Browser, type BrowserContext, chromium } from 'playwright';
import { AuthPage } from '../pages/auth-page';
import { DashboardPage } from '../pages/dashboard-page';
import { UserConfig } from '../simulation.config';

export abstract class BaseUser {
  protected context: BrowserContext;

  constructor(protected config: UserConfig, protected browser: Browser) {}

  async initialize() {
    this.context = await this.browser.newContext({
      viewport: { width: 1280, height: 720 },
    });
    const page = await this.context.newPage();
    await this.context.addInitScript(() => {
      // Disable autoplay for videos to reduce resource usage
      window.localStorage.setItem('playwright-video-disabled', 'true');
    });
  }

  abstract async runWorkflow(): Promise<void>;

  async cleanup() {
    await this.context?.close();
  }

  protected getPosition() {
    // Position windows in a grid: 5 rows x 4 cols
    const row = Math.floor((this.config.id - 1) / 4);
    const col = (this.config.id - 1) % 4;
    return { x: col * 320, y: row * 200 };
  }
}
```

- [ ] **Step 2: Commit**
```bash
git add tests/BrowserSimulation/users/base-user.ts
git commit -m "feat: add BaseUser class with initialization and cleanup"
```

---

## Task 6: Create User Workflows

**Files:**
- Create: `tests/BrowserSimulation/users/user-workflows.ts`

**Interfaces:**
- Consumes: `BaseUser`, `AuthPage`, `DashboardPage`
- Produces: `SalesUser`, `ManagerUser`, `AdminUser`, `GuestUser` classes

- [ ] **Step 1: Create user-workflows.ts**
```typescript
import { Page } from '@playwright/test';
import { BaseUser } from './base-user';
import { AuthPage } from '../pages/auth-page';
import { DashboardPage } from '../pages/dashboard-page';

export class SalesUser extends BaseUser {
  async runWorkflow() {
    const page = await this.context.newPage();
    const auth = new AuthPage(page);
    const dashboard = new DashboardPage(page);

    await auth.goto();
    await auth.login(this.config.email, this.config.password);
    await dashboard.waitForLoad();

    // Sales rep workflow: deals and contacts
    await dashboard.navigateToDeals();
    await page.waitForTimeout(2000);
    await dashboard.navigateToContacts();
    await page.waitForTimeout(2000);

    console.log(`User ${this.config.id} (Sales) completed workflow`);
  }
}

export class ManagerUser extends BaseUser {
  async runWorkflow() {
    const page = await this.context.newPage();
    const auth = new AuthPage(page);
    const dashboard = new DashboardPage(page);

    await auth.goto();
    await auth.login(this.config.email, this.config.password);
    await dashboard.waitForLoad();

    // Manager workflow: review pipeline
    await dashboard.navigateToDeals();
    await page.waitForTimeout(3000);

    console.log(`User ${this.config.id} (Manager) completed workflow`);
  }
}

export class AdminUser extends BaseUser {
  async runWorkflow() {
    const page = await this.context.newPage();
    const auth = new AuthPage(page);
    const dashboard = new DashboardPage(page);

    await auth.goto();
    await auth.login(this.config.email, this.config.password);
    await dashboard.waitForLoad();

    // Admin workflow: browse settings
    await page.goto('/settings');
    await page.waitForTimeout(2000);

    console.log(`User ${this.config.id} (Admin) completed workflow`);
  }
}

export class GuestUser extends BaseUser {
  async runWorkflow() {
    const page = await this.context.newPage();
    // Guest can only access public pages
    await page.goto('/');
    await page.waitForTimeout(1000);
    console.log(`User ${this.config.id} (Guest) completed workflow`);
  }
}
```

- [ ] **Step 2: Commit**
```bash
git add tests/BrowserSimulation/users/user-workflows.ts
git commit -m "feat: add user workflow classes for all personas"
```

---

## Task 7: Create Main Orchestrator

**Files:**
- Create: `tests/BrowserSimulation/orchestrator.ts`

**Interfaces:**
- Consumes: All user workflow classes, `userPersonas`
- Produces: Running simulation with 20 visible browsers

- [ ] **Step 1: Create orchestrator.ts**
```typescript
import { chromium, Browser } from 'playwright';
import { userPersonas, UserConfig } from './simulation.config';
import { SalesUser, ManagerUser, AdminUser, GuestUser } from './users/user-workflows';
import { BaseUser } from './users/base-user';

async function createUser(config: UserConfig, browser: Browser): Promise<BaseUser> {
  switch (config.persona) {
    case 'sales': return new SalesUser(config, browser);
    case 'manager': return new ManagerUser(config, browser);
    case 'admin': return new AdminUser(config, browser);
    case 'guest': return new GuestUser(config, browser);
    default: throw new Error(`Unknown persona: ${config.persona}`);
  }
}

async function main() {
  console.log('Starting 20-user browser simulation...');
  const browser = await chromium.launch({ headless: false });

  const users = await Promise.all(
    userPersonas.map(c => createUser(c, browser))
  );

  console.log('Launching browsers...');
  
  // Initialize all users
  for (const user of users) {
    await user.initialize();
  }

  console.log('Running workflows...');
  
  // Run all workflows in parallel
  const results = await Promise.all(
    users.map(async (user, i) => {
      try {
        await user.runWorkflow();
        return { id: i + 1, success: true };
      } catch (error) {
        console.error(`User ${i + 1} failed:`, error);
        return { id: i + 1, success: false, error };
      }
    })
  );

  // Cleanup
  for (const user of users) {
    await user.cleanup();
  }
  
  await browser.close();

  // Summary
  const passed = results.filter(r => r.success).length;
  const failed = results.filter(r => !r.success).length;
  console.log(`\\nSimulation complete: ${passed} passed, ${failed} failed`);
}

main().catch(console.error);
```

- [ ] **Step 2: Add script to package.json**
```json
"scripts": {
  "simulate": "playwright test tests/BrowserSimulation/orchestrator.ts",
  "simulate:dev": "ts-node tests/BrowserSimulation/orchestrator.ts"
}
```

- [ ] **Step 3: Commit**
```bash
git add tests/BrowserSimulation/orchestrator.ts
git commit -m "feat: add main orchestrator for 20-user simulation"
```

---

## Plan Complete and Saved to `docs/superpowers/plans/2026-07-03-browser-user-simulation.md`.

**Two execution options:**

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?