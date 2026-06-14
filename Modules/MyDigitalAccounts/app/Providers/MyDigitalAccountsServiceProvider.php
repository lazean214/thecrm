<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MyDigitalAccounts\Actions\CreateCompanyAction;
use Modules\MyDigitalAccounts\Actions\CreateEmployeeAction;
use Modules\MyDigitalAccounts\Actions\CreateInvoiceAction;
use Modules\MyDigitalAccounts\Actions\FetchCompaniesAction;
use Modules\MyDigitalAccounts\Actions\FetchEmployeesAction;
use Modules\MyDigitalAccounts\Actions\FetchInvoicesAction;
use Modules\MyDigitalAccounts\Actions\UpdateCompanyAction;
use Modules\MyDigitalAccounts\Domain\MyDigitalAccountsClient;

/**
 * MyDigitalAccounts Service Provider
 *
 * Registers all MyDigitalAccounts module services in the Laravel Service Container
 * as singletons. This provider should be registered in config/app.php
 *
 * Add to config/app.php:
 * 'providers' => [
 *     ...
 *     MyDigitalAccounts\Providers\MyDigitalAccountsServiceProvider::class,
 * ]
 */
class MyDigitalAccountsServiceProvider extends ServiceProvider
{
    /**
     * Service provider boot
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/mydigitalaccounts.php' => config_path('mydigitalaccounts.php'),
        ], 'mydigitalaccounts-config');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'mydigitalaccounts');
    }

    /**
     * Register services in the container
     */
    public function register(): void
    {
        // Load configuration if not already published
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mydigitalaccounts.php',
            'mydigitalaccounts'
        );

        // Register the API Client as a singleton
        $this->app->singleton(MyDigitalAccountsClient::class, function ($app) {
            $config = $app['config']['mydigitalaccounts'];

            return new MyDigitalAccountsClient($config);
        });

        // Register Company Actions
        $this->registerCompanyActions();

        // Register Invoice Actions
        $this->registerInvoiceActions();

        // Register Employee Actions
        $this->registerEmployeeActions();

        // Register Manager/Facade bindings
        $this->registerManagers();
    }

    /**
     * Register Company-related action classes
     */
    private function registerCompanyActions(): void
    {
        $this->app->singleton(FetchCompaniesAction::class, function ($app) {
            return new FetchCompaniesAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });

        $this->app->singleton(CreateCompanyAction::class, function ($app) {
            return new CreateCompanyAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });

        $this->app->singleton(UpdateCompanyAction::class, function ($app) {
            return new UpdateCompanyAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });
    }

    /**
     * Register Invoice-related action classes
     */
    private function registerInvoiceActions(): void
    {
        $this->app->singleton(FetchInvoicesAction::class, function ($app) {
            return new FetchInvoicesAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });

        $this->app->singleton(CreateInvoiceAction::class, function ($app) {
            return new CreateInvoiceAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });
    }

    /**
     * Register Employee-related action classes
     */
    private function registerEmployeeActions(): void
    {
        $this->app->singleton(FetchEmployeesAction::class, function ($app) {
            return new FetchEmployeesAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });

        $this->app->singleton(CreateEmployeeAction::class, function ($app) {
            return new CreateEmployeeAction(
                $app->make(MyDigitalAccountsClient::class)
            );
        });
    }

    /**
     * Register Manager/Facade bindings for convenience
     *
     * This allows accessing the client via: app('mydigitalaccounts.client')
     */
    private function registerManagers(): void
    {
        $this->app->bind('mydigitalaccounts.client', function ($app) {
            return $app->make(MyDigitalAccountsClient::class);
        });

        $this->app->bind('mydigitalaccounts.companies', function ($app) {
            return new CompanyManager(
                $app->make(FetchCompaniesAction::class),
                $app->make(CreateCompanyAction::class),
                $app->make(UpdateCompanyAction::class),
            );
        });

        $this->app->bind('mydigitalaccounts.invoices', function ($app) {
            return new InvoiceManager(
                $app->make(FetchInvoicesAction::class),
                $app->make(CreateInvoiceAction::class),
            );
        });

        $this->app->bind('mydigitalaccounts.employees', function ($app) {
            return new EmployeeManager(
                $app->make(FetchEmployeesAction::class),
                $app->make(CreateEmployeeAction::class),
            );
        });
    }

    /**
     * Get the services provided by this provider
     */
    public function provides(): array
    {
        return [
            MyDigitalAccountsClient::class,
            FetchCompaniesAction::class,
            CreateCompanyAction::class,
            UpdateCompanyAction::class,
            FetchInvoicesAction::class,
            CreateInvoiceAction::class,
            FetchEmployeesAction::class,
            CreateEmployeeAction::class,
            'mydigitalaccounts.client',
            'mydigitalaccounts.companies',
            'mydigitalaccounts.invoices',
            'mydigitalaccounts.employees',
        ];
    }
}

/**
 * Company Manager - Convenience class for managing company operations
 *
 * Usage:
 * $manager = app('mydigitalaccounts.companies');
 * $companies = $manager->fetchAll(page: 1);
 * $company = $manager->create(['name' => 'Acme Inc', 'email' => 'info@acme.com']);
 */
class CompanyManager
{
    public function __construct(
        private FetchCompaniesAction $fetchAction,
        private CreateCompanyAction $createAction,
        private UpdateCompanyAction $updateAction,
    ) {}

    /**
     * Fetch all companies with pagination
     */
    public function fetchAll(int $page = 1, int $perPage = 15, array $filters = [])
    {
        return $this->fetchAction->execute($page, $perPage, $filters);
    }

    /**
     * Fetch a single company by ID
     */
    public function fetchById(string $companyId)
    {
        return $this->fetchAction->fetchById($companyId);
    }

    /**
     * Create a new company
     */
    public function create(array $data)
    {
        return $this->createAction->execute($data);
    }

    /**
     * Update an existing company
     */
    public function update(string $companyId, array $data)
    {
        return $this->updateAction->execute($companyId, $data);
    }
}

/**
 * Invoice Manager - Convenience class for managing invoice operations
 */
class InvoiceManager
{
    public function __construct(
        private FetchInvoicesAction $fetchAction,
        private CreateInvoiceAction $createAction,
    ) {}

    /**
     * Fetch all invoices with pagination
     */
    public function fetchAll(int $page = 1, int $perPage = 15, array $filters = [])
    {
        return $this->fetchAction->execute($page, $perPage, $filters);
    }

    /**
     * Fetch a single invoice by ID
     */
    public function fetchById(string $invoiceId)
    {
        return $this->fetchAction->fetchById($invoiceId);
    }

    /**
     * Fetch invoices by company ID
     */
    public function fetchByCompanyId(string $companyId, int $page = 1, int $perPage = 15)
    {
        return $this->fetchAction->fetchByCompanyId($companyId, $page, $perPage);
    }

    /**
     * Fetch invoices by status
     */
    public function fetchByStatus(string $status, int $page = 1, int $perPage = 15)
    {
        return $this->fetchAction->fetchByStatus($status, $page, $perPage);
    }

    /**
     * Create a new invoice
     */
    public function create(array $data)
    {
        return $this->createAction->execute($data);
    }
}

/**
 * Employee Manager - Convenience class for managing employee operations
 */
class EmployeeManager
{
    public function __construct(
        private FetchEmployeesAction $fetchAction,
        private CreateEmployeeAction $createAction,
    ) {}

    /**
     * Fetch all employees with pagination
     */
    public function fetchAll(int $page = 1, int $perPage = 15, array $filters = [])
    {
        return $this->fetchAction->execute($page, $perPage, $filters);
    }

    /**
     * Fetch a single employee by ID
     */
    public function fetchById(string $employeeId)
    {
        return $this->fetchAction->fetchById($employeeId);
    }

    /**
     * Fetch employees by company ID
     */
    public function fetchByCompanyId(string $companyId, int $page = 1, int $perPage = 15)
    {
        return $this->fetchAction->fetchByCompanyId($companyId, $page, $perPage);
    }

    /**
     * Fetch active employees
     */
    public function fetchActive(int $page = 1, int $perPage = 15)
    {
        return $this->fetchAction->fetchActive($page, $perPage);
    }

    /**
     * Create a new employee
     */
    public function create(array $data)
    {
        return $this->createAction->execute($data);
    }
}
