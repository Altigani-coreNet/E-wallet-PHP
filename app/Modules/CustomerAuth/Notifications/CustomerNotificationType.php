<?php

namespace App\Modules\CustomerAuth\Notifications;

enum CustomerNotificationType: string
{
    case ProfileCompleted = 'profile_completed';
    case PasswordChanged = 'password_changed';
    case CashIn = 'cash_in';
    case TransferSent = 'transfer_sent';
    case TransferReceived = 'transfer_received';

    public function topic(): string
    {
        return match ($this) {
            self::ProfileCompleted, self::PasswordChanged => 'alert',
            self::CashIn, self::TransferSent, self::TransferReceived => 'payments',
        };
    }

    public function title(array $context = []): string
    {
        return match ($this) {
            self::ProfileCompleted => 'Application received - we\'re on it',
            self::PasswordChanged => 'Password updated',
            self::CashIn => 'Money added to your wallet',
            self::TransferSent => 'Transfer sent',
            self::TransferReceived => 'Money received',
        };
    }

    public function body(array $context = []): string
    {
        return match ($this) {
            self::ProfileCompleted => 'Thanks for completing your profile. We\'ve received your application and our team is reviewing it now. We\'ll let you know the moment your account is approved - no action needed from you.',
            self::PasswordChanged => 'Your account password was changed successfully. If you didn\'t make this change, please contact support immediately.',
            self::CashIn => sprintf(
                'Your wallet was credited with %s %s.',
                $this->formatAmount($context['amount'] ?? 0),
                $context['currency'] ?? 'SDG',
            ),
            self::TransferSent => sprintf(
                'You sent %s %s to %s.',
                $this->formatAmount($context['amount'] ?? 0),
                $context['currency'] ?? 'SDG',
                $context['recipient_name'] ?? 'another customer',
            ),
            self::TransferReceived => sprintf(
                'You received %s %s from %s.',
                $this->formatAmount($context['amount'] ?? 0),
                $context['currency'] ?? 'SDG',
                $context['sender_name'] ?? 'another customer',
            ),
        };
    }

    private function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
