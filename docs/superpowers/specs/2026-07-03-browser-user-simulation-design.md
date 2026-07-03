# Browser-Based User Simulation and UI Testing Design

**Date:** 2026-07-03
**Author:** Claude
**Status:** Approved

## Overview

Create a visible browser-based user simulation system for testing with 20 simultaneous users, using Playwright for robust browser automation with visible Chrome windows.

## Architecture

```
┌─────────────────────────────────────────────────────┐
│         orchestrator.ts (Node.js entrypoint)        │
│  - Creates 20 isolated browser contexts             │
│  - Coordinates user simulation lifecycle            │
│  - Reports real-time status to console              │
└──────────────────┬────────────────────────────────┘
                   │
         ┌─────────┴─────────┐
         │  Playwright API   │
         └─────────┬─────────┘
                   │
      ┌────────────┼────────────┐
      │            │            │
  User[1-20]   User[1-20]   User[1-20]
(visible      (visible      (visible
browser       browser       browser
windows)      windows)      windows)
```

## Components

### 1. User Configuration (`simulation.config.ts`)
- Defines 20 user profiles with different permissions/statuses
- Each profile includes: login credentials, navigation paths, interaction scripts
- Configurable wait times between actions for realistic pacing

### 2. Orchestrator (`orchestrator.ts`)
- Spawns 20 browser contexts with unique storage states
- Positions windows in a grid layout for visibility
- Monitors all users, reports errors/failures
- Graceful shutdown handling on SIGINT/SIGTERM

### 3. User Simulation Scripts (`users/`)
- `base-user.ts` - Common actions (login, navigation, logout)
- `user-workflows.ts` - Specific workflows per user persona
- Actions: click, type, wait, assert element states, screenshot on error

### 4. Laravel Test Helper (`routes/simulation.php`)
- Endpoints to create/cleanup test users
- Seed database with realistic CRM data
- Health check endpoint

### 5. Page Objects (`tests/BrowserSimulation/pages/`)
- Reusable selectors for CRM pages
- Dashboard, Contacts, Deals, Auth pages
- Follows Playwright best practices

## Implementation Files to Create

| File | Purpose |
|------|---------|
| `playwright.config.ts` | Playwright base configuration |
| `simulation.config.ts` | 20 user profile definitions |
| `orchestrator.ts` | Main runner script |
| `users/base-user.ts` | Shared user actions |
| `users/user-workflows.ts` | Workflow definitions |
| `routes/simulation.php` | Laravel helper routes |
| `tests/BrowserSimulation/pages/*.ts` | Page object models |

## Data Flow

1. `npm run simulate` → orchestrator launches
2. Each user: navigates to login → authenticates → executes CRM workflow
3. Real-time: Console shows which users are active/complete
4. On error: Screenshot saved, error logged, user marked failed
5. End: All browsers close, summary report displayed

## User Personas

| Persona | Count | Permissions | Workflow |
|---------|-------|-------------|----------|
| Sales Rep | 8 | View/create deals, contacts | Navigate deals → view contact → create deal |
| Manager | 6 | Full access, stages/admin | Dashboard → manage stages → review pipeline |
| Admin | 4 | System settings | Users → settings → audit logs |
| Guest/Test | 2 | Limited/read-only | Browse only, no data modification |

## Resource Considerations

- ~20 Chrome browser windows (~400-800MB total RAM)
- Runs on localhost:8000 Laravel dev server
- Test database (sqlite in-memory or separate test DB)
- CPU usage scales with parallel DOM operations

## Success Criteria

- All 20 browser windows open and navigate successfully
- Each user completes their assigned workflow without errors
- Real-time status updates visible in terminal
- Summary report shows pass/fail counts
- Clean shutdown on Ctrl+C

## Future Extensions

- Add more user personas easily
- Record videos of failures
- Export timing/performance metrics
- Integrate with CI via headless mode toggle