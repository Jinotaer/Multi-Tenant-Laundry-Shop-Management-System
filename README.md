# Multi-Tenant Laundry Shop Management System

A comprehensive, modern Laravel-based SaaS platform designed to streamline laundry shop operations through multi-tenant architecture. This system enables laundry shop owners to manage customers, orders, services, payments, and subscriptions across multiple shop instances on a single application infrastructure.

## Features

### Multi-Tenancy
- **Isolated Tenant Databases** — Each laundry shop operates with complete data isolation
- **Domain-Based Routing** — Automatic tenant identification via subdomain (e.g., `shop-name.localhost`)
- **Shared Infrastructure** — Cost-effective single codebase supporting unlimited tenants

### Authentication & Authorization
- **Multi-User Types** — Support for Owners, Managers/Staff, and Customers
- **Role-Based Access Control** — Fine-grained permissions and policies
- **Unified Login** — Single login interface for all user types
- **Session Management** — Secure session handling with remember functionality

### Customer Management
- **Customer Profiles** — Complete customer information with contact details
- **Loyalty Programs** — Track and manage customer loyalty points and rewards
- **Order History** — Complete order tracking and history per customer
- **Notes & Preferences** — Store customer preferences and special notes

### Order Management
- **Order Creation & Tracking** — Create and track customer orders from pickup to delivery
- **Order Status Workflow** — Track order progression through custom statuses
- **Service Selection** — Assign multiple services to orders (washing, ironing, dry cleaning, etc.)
- **Due Date Management** — Automatic and manual due date assignment
- **Order Status Notifications** — Automated customer notifications on status changes

### Services & Pricing
- **Service Catalog** — Define and manage multiple laundry services
- **Flexible Pricing** — Per-service pricing configuration
- **Service Categories** — Organize services by type

### Payment Processing
- **PayMongo Integration** — Secure payment processing via PayMongo
- **Multi-Payment Methods** — Support for various payment channels
- **Payment Tracking** — Complete payment history and reconciliation
- **Invoice Generation** — Automated invoice creation for orders

### Subscription Plans
- **Flexible Subscription Model** — Multiple tier subscription plans for shops
- **Feature Limits** — Configure feature access based on subscription tier
- **Plan Management** — Switch between plans and manage subscriptions
- **Trial Periods** — Support for trial subscriptions

### Analytics & Reporting
- **Expense Tracking** — Monitor shop operational expenses
- **Order Analytics** — Track order volume and trends
- **Revenue Reports** — Income and payment analytics

### Theme & Branding
- **Multi-Theme Support** — Customize UI appearance with themes
- **Tenant Branding** — Per-shop logo and theme customization
- **Dynamic Theming** — Switch between light/dark modes

### Notifications
- **Email Notifications** — Automated order status emails to customers
- **Admin Alerts** — Notifications for shop administrators
- **Customizable templates** — Email templates for various events

## Technology Stack

- **Framework:** Laravel 12
- **Language:** PHP 8.2.12
- **Multi-Tenancy:** Stancl/Tenancy v2
- **Frontend:** Tailwind CSS v3, Alpine.js v3
- **Testing:** Pest 3
- **Code Quality:** Laravel Pint
- **Authentication:** Laravel Breeze
- **UI Components:** Laravel Blade
- **Payment Gateway:** PayMongo
- **Database:** MySQL
- **Task Scheduling:** Laravel Scheduler

## Project Structure

```
├── app/
│   ├── Console/          # Artisan commands
│   ├── Http/             # Controllers, requests, middleware
│   ├── Listeners/        # Event listeners
│   ├── Mail/             # Mailable classes for notifications
│   ├── Models/           # Eloquent models
│   ├── Providers/        # Service providers
│   └── Services/         # Business logic services
├── database/
│   ├── migrations/       # Central migrations
│   ├── migrations/tenant # Tenant-specific migrations
│   ├── factories/        # Model factories for testing
│   └── seeders/          # Database seeders
├── resources/
│   ├── css/              # Tailwind CSS
│   ├── js/               # Alpine.js and front-end scripts
│   └── views/            # Blade templates
├── routes/
│   ├── web.php           # Web routes
│   ├── tenant.php        # Tenant routes
│   ├── auth.php          # Authentication routes
│   └── console.php       # Console commands
├── tests/                # Test suites (Feature & Unit)
└── storage/              # Logs and file storage
```

## Core Models

- **Admin** — Central platform administrators
- **User** — Tenant staff/managers (owners, managers, employees)
- **Customer** — End customers of laundry shops
- **Order** — Customer orders with services
- **Service** — Laundry services offered
- **Payment** — Payment records
- **SubscriptionPlan** — Available subscription tiers
- **Tenant** — Shop/business information
- **TenantRegistration** — New shop registration requests

## Getting Started

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & npm

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd laundry-shop-management
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan tenants:migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the application**
   ```bash
   php artisan serve
   php artisan queue:work  # For background jobs
   ```

## User Roles & Permissions

### Admin
- Platform administration
- Tenant management
- Subscription management

### Owner/Manager
- Complete shop management
- Staff management
- Customer management
- Order and service management
- Payment and revenue tracking
- Theme and branding customization

### Staff
- Order management
- Customer interaction
- Basic reporting

### Customer
- View order status
- Track deliveries
- Payment history
- Account management

## Key Features in Action

### Order Workflow
1. Customer submits order or staff creates order
2. Admin assigns services and due date
3. Customer receives order status notifications
4. Order progresses through workflow (received → processing → ready → completed)
5. Payment processing and receipt

### Multi-Tenancy in Action
- Each shop operates independently with its own database schema
- Automatic tenant detection via subdomain
- Seamless switching between shops
- Isolated data with shared codebase

## Testing

Run the test suite:
```bash
php artisan test
php artisan test --compact  # Verbose output
```

Test specific features:
```bash
php artisan test --filter=CustomerLoginTest
```

## Code Quality

Format code with Pint:
```bash
vendor/bin/pint
```

## Deployment

The application is optimized for deployment on shared hosting or cloud platforms with multi-tenancy support. Key considerations:
- Database isolation per tenant
- Environment-based configuration
- Queue job processing for background tasks
- Email configuration for notifications

## Contributing

This is a commercial SaaS application. Please follow the established coding standards and testing requirements when contributing.

## License

Proprietary Software. All rights reserved.

## Support

For support and feature requests, please contact the development team.

---

**Built with ❤️ using Laravel and Modern PHP**

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
