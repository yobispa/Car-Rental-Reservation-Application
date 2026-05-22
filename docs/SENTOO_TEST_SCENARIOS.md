# Sentoo Test Scenarios

Use a normal reservation first, then complete the payment on the Sentoo sandbox mock screen.

## TC001 - Successful Payment

1. Reserve a car.
2. On the Sentoo mock screen, choose `success`.
3. Return to the app with `?attempt=success`.

Expected result:

- Reservation status becomes `CONFIRMED`.
- Payment status becomes `success`.
- The customer is not shown a retry button.

## TC002 - Pending Payment

1. Reserve a car.
2. On the Sentoo mock screen, choose `pending`.
3. Return to the app with `?attempt=pending`.

Expected result:

- Reservation status stays `PENDING`.
- Payment status is not `success`.
- The customer sees that payment is still pending.
- The customer can continue the existing payment, but cannot start a brand-new retry payment yet.

## TC003 - Unsuccessful Payment

1. Reserve a car.
2. On the Sentoo mock screen, choose `cancelled` or `rejected`.
3. Return to the app with `?attempt=cancelled` or `?attempt=rejected`.

Expected result:

- Reservation status becomes `CANCELLED`.
- Payment status becomes `cancelled` or `rejected`.
- The payment message is shown when Sentoo provides one.
- The customer can click `Try payment again`.

## TC004 - Asynchronous Success Payment

1. Reserve a car.
2. Copy the Sentoo payment URL.
3. Complete the first attempt as `pending`.
4. Return to the app with `?attempt=pending`.
5. Open the copied Sentoo payment URL again.
6. Complete the payment as `success`.
7. Return to the app with `?attempt=success`.

Expected result:

- Before success, the app shows pending payment.
- After success, reservation status becomes `CONFIRMED`.
- Payment status becomes `success`.
- The retry button is not shown after success.

## TC005 - URL Status Manipulation

1. Reserve a car.
2. Complete the payment as `rejected`.
3. Return to the app with `?attempt=rejected`.
4. Manually change the URL to `?attempt=success`.

Expected result:

- The app does not trust the URL attempt value as the final payment status.
- The app asks Sentoo for the real transaction status.
- The reservation remains rejected/cancelled unless Sentoo returns `success`.
