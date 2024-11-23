# Credit Transaction Service

A PHP-based service for managing user credits and transactions with comprehensive reporting capabilities. This service is built using modern PHP practices, following hexagonal architecture and SOLID principles.

## 🏗️ Architecture & Patterns

### Architecture
    - Domain-centric design
    - Clear separation of concerns
    - Infrastructure independence

### Design Patterns
- Repository Pattern
- Command Pattern
- Service Layer Pattern
- Factory Pattern
- Dependency Injection

## 🚀 Requirements

- PHP 7.4+
- Composer
- MySQL/MariaDB
- Redis
- Docker
- PDO PHP Extension
- Redis PHP Extension

## 📦 Installation

### Using Docker (Recommended)

1. Clone the repository
```bash
git clone https://github.com/mohaphez/credit-transaction-service.git
cd credit-transaction-service
```

2. Build and start containers
```bash
docker-compose up -d --build
```

3. Install dependencies
```bash
docker-compose exec app composer install
```

4. Set up environment
```bash
cp .env.example .env
```

### Manual Installation

1. Clone and install dependencies
```bash
git clone https://github.com/mohaphez/credit-transaction-service.git
cd credit-transaction-service
composer install
```

2. Configure environment
```bash
cp .env.example .env
# Edit .env with your database credentials
```

## 🛠️ Usage

### Console Commands

1. Populate Users
```bash
# Generate random users
php bin/console.php users:populate --count=10

# Using Docker
docker-compose exec app php bin/console.php users:populate --count=10
```

2. Process Transaction
```bash
# Process a transaction for a user
php bin/console.php transaction:process {userId} {amount}

# Example
php bin/console.php transaction:process 1 100.50

# Using Docker
docker-compose exec app php bin/console.php transaction:process 1 100.50
```

3. Generate User Report
```bash
# Get daily report for a user
php bin/console.php report:user-daily {userId} {date}

# Example
php bin/console.php report:user-daily 1 2024-11-23

# Using Docker
docker-compose exec app php bin/console.php report:user-daily 1 2024-11-23
```

4. Generate System Report

```bash
# Get system-wide daily report
php bin/console.php report:system-daily {date}

# Example
php bin/console.php report:system-daily 2024-11-23

# Using Docker
docker-compose exec app php bin/console.php report:system-daily 2024-11-23
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
```

### Using Docker
```bash
docker-compose exec app ./vendor/bin/phpunit
```

## 💅 Code Style

This project follows PSR-12 coding standards and uses Laravel Pint for code style enforcement.

### Running Pint

```bash
# Show code style issues
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint

# Using Docker
docker-compose exec app ./vendor/bin/pint
```

## 🏗️ Infrastructure

### Services Used
- **MySQL/MariaDB**: Primary data storage
- **Redis**: Caching layer for reports
- **PHP-FPM**: Application server

### Docker Services
- `app`: PHP Application
- `db`: MySQL Database
- `redis`: Redis Cache

### Cache Strategy
- Report caching with Redis
- Configurable TTL
- Automatic cache invalidation on updates

## 📝 Code Quality Tools

- PHPUnit for testing
- Laravel Pint for code style

```bash
# Run individual checks
composer test      # Run tests
composer pint        # Check code style
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request