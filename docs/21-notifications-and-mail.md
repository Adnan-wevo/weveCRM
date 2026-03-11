# Notifications and Mail

## Overview

The application uses Laravel's notification system for transactional emails and Mailpit as a development mail catcher. Notifications are sent via the `mail` channel. In development, all outgoing email is captured by Mailpit and viewable at `http://localhost:8025`.

## Mail Configuration

### Development Settings

| Setting | Value | Purpose |
|---------|-------|---------|
| `MAIL_MAILER` | `smtp` | Transport driver |
| `MAIL_HOST` | `mailpit` | Docker service hostname |
| `MAIL_PORT` | `1025` | Mailpit SMTP port |
| `MAIL_USERNAME` | `null` | No authentication required |
| `MAIL_PASSWORD` | `null` | No authentication required |
| `MAIL_ENCRYPTION` | `null` | No TLS in development |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default sender |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Default sender name |

### Mailpit

Mailpit captures all outgoing SMTP traffic during development:

- **Web UI**: `http://localhost:8025` -- browse captured emails.
- **SMTP**: Port 1025 -- receives all application mail.
- No emails leave the development environment.

## Notification Classes

### MagicLinkNotification

**Package**: `grovyle/laravel-magic-login`

Sends a passwordless login link to the user's email address.

**Trigger**: User requests a magic login from the `MagicLogin` Livewire component.

**Channel**: `mail`

**Content**:

- Subject: Magic login link.
- Body: Signed URL with a time-limited token.
- Action button linking to the signed URL.

### Fortify Notifications

Laravel Fortify dispatches the following notifications via Laravel's built-in notification classes:

| Notification | Trigger | Content |
|--------------|---------|---------|
| `ResetPassword` | User requests password reset | Signed URL to the password reset form |
| `VerifyEmail` | User registers or changes email | Signed URL to verify the email address |

These use Laravel's default `Illuminate\Auth\Notifications\ResetPassword` and `Illuminate\Auth\Notifications\VerifyEmail` classes.

### Job Completion Notifications

Import and export operations dispatch queued notification jobs upon completion:

| Job | Trigger | Content |
|-----|---------|---------|
| `NotifyUserOfCompletedExport` | Export job finishes | Notification that the export file is ready for download |
| `NotifyUserOfCompletedImport` | Import job finishes | Notification with import results (success/failure counts) |

These notifications are delivered through the Livewire components via toast events rather than email by default.

## Creating New Notifications

### Generate the Notification

```bash
php artisan make:notification OrderConfirmedNotification
```

### Define Channels and Content

```php
class OrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmed')
            ->line('Your order #' . $this->order->id . ' has been confirmed.')
            ->action('View Order', route('orders.show', $this->order))
            ->line('Thank you for your purchase.');
    }
}
```

### Send the Notification

```php
$user->notify(new OrderConfirmedNotification($order));
```

### Queue the Notification

Add `implements ShouldQueue` to the notification class for asynchronous delivery:

```php
class OrderConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

Queued notifications are processed by Horizon.

## Toast Notifications

The application also uses client-side toast notifications (not email) for real-time feedback:

```php
// In a Livewire component
$this->dispatch('toast', type: 'success', message: 'Profile updated.');
```

The Alpine.js toast component in `resources/views/components/partials/toast.blade.php` handles display. See [Frontend Architecture](12-frontend-architecture.md) for details.

## Testing Mail

### Mailpit Web Interface

All emails sent during development are captured at `http://localhost:8025`. The interface shows:

- Sender and recipient.
- Subject and body.
- HTML and plain text rendering.
- Attachments.

### Test Environment

In the test environment, the mail driver is set to `array`, which prevents actual email sending and allows assertion on sent mail:

```php
Mail::assertSent(OrderConfirmedNotification::class);
```
