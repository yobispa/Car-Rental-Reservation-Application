# Car Rental Reservation Application

Symfony car rental reservation app with car inventory, Smart Match, reservation rules, dynamic pricing, Sentoo sandbox payments, and webhook support.

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL or MariaDB, for example XAMPP
- Symfony CLI is optional. The PHP built-in server also works.

## Installation

1. Install PHP dependencies:

```bash
composer install
```

2. Create `.env.local` and set your local database and Sentoo sandbox values:

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/car-reservation?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
SENTOO_API_BASE_URL="https://api.sandbox.sentoo.io"
SENTOO_MERCHANT_ID="your-merchant-id"
SENTOO_SECRET="your-secret-key"
SENTOO_CURRENCY="XCG"
SENTOO_RETURN_URL="http://127.0.0.1:8000/payment/return/{id}?attempt="
```

3. Create the database and run migrations:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

4. Start the application:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000`.

## Main Features

- Starter fleet with minivans, sedans, and convertibles.
- Reservations store customer, car, number of persons, start date, and end date.
- Rental duration is limited to 1 to 30 days.
- Customers cannot rent again until 5 days after their previous active reservation ends.
- Direct car reservation checks that the car is not double-booked.
- Smart Match shows available cars sorted by the lowest estimated price.
- Pricing can increase for summer, December, high demand, and low availability.
- If no cars are available, Smart Match suggests alternative dates.
- Sentoo sandbox payment creation, return handling, retry flow, and webhook status update.
- Basic logging for payment creation, retries, returns, and webhook updates.

## Sentoo Testing

The payment return URL used locally is:

```text
http://127.0.0.1:8000/payment/return/{id}?attempt=
```

For example:

```text
http://127.0.0.1:8000/payment/return/49?attempt=success
```

The webhook endpoint is:

```text
http://127.0.0.1:8000/sentoo/webhook
```

For local webhook testing with Sentoo, expose the local app with ngrok:

```bash
ngrok http 8000
```

Then use the ngrok HTTPS URL plus `/sentoo/webhook`.

The manual TC001-TC005 payment checklist is in `docs/SENTOO_TEST_SCENARIOS.md`.

## Running Checks

```bash
php bin/console lint:twig templates
php bin/console lint:container
php bin/console doctrine:schema:validate
```

To run the automated tests:

```bash
php bin/console cache:clear --env=test
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/phpunit
```

The test environment uses a fake Sentoo service so PHPUnit can test the reservation/payment redirect flow without calling the real sandbox API.

## Notes For Assessment

- The app follows Symfony MVC: controllers handle requests, entities model data, forms validate input, Twig renders pages, and the Sentoo service handles the external payment API.
- Payment secrets are read from environment variables and should stay out of Git.
- Repositories are kept simple and default; the reservation rules and pricing helper functions are written in the controller so the flow is easy to explain.

## Architecture Notes

- The database is the source of truth for reservations and payment status.
- A reservation is first saved with `PENDING` status, then Sentoo creates the external payment. This keeps the reservation traceable even if the payment request fails.
- The return route and webhook both ask Sentoo for the real payment status instead of trusting only the `attempt` value in the URL.
- For a larger system, payment creation and webhook processing could move to a queue or separate service, but the current project keeps them inside Symfony so the assessment is easy to install and review.
- For stronger production consistency, direct reservation creation could be wrapped in a database transaction and lock the selected car/date range before creating the payment.
