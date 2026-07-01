<?php

namespace App\Modules\CustomerAuth\Notifications;

enum CustomerNotificationType: string
{
    case ProfileCompleted = 'profile_completed';
    case ProfileRejected = 'profile_rejected';
    case ProfileApproved = 'profile_approved';
    case ProfileChangeRequestApproved = 'profile_change_request_approved';
    case ProfileChangeRequestRejected = 'profile_change_request_rejected';
    case PasswordChanged = 'password_changed';
    case CashIn = 'cash_in';
    case TransferSent = 'transfer_sent';
    case TransferReceived = 'transfer_received';
    case BillPayment = 'bill_payment';

    public function topic(): string
    {
        return match ($this) {
            self::ProfileCompleted,
            self::ProfileRejected,
            self::ProfileApproved,
            self::ProfileChangeRequestApproved,
            self::ProfileChangeRequestRejected,
            self::PasswordChanged => 'alert',
            self::CashIn, self::TransferSent, self::TransferReceived, self::BillPayment => 'payments',
        };
    }

    public function title(array $context = []): string
    {
        return match ($this) {
            self::ProfileCompleted => 'Application received - we\'re on it',
            self::ProfileRejected => 'Profile update required',
            self::ProfileApproved => 'Your account is approved',
            self::ProfileChangeRequestApproved => 'Profile update approved',
            self::ProfileChangeRequestRejected => 'Profile update not approved',
            self::PasswordChanged => 'Password updated',
            self::CashIn => 'Money added to your wallet',
            self::TransferSent => 'Transfer sent',
            self::TransferReceived => 'Money received',
            self::BillPayment => 'Bill paid',
        };
    }

    public function body(array $context = []): string
    {
        return match ($this) {
            self::ProfileCompleted => 'Thanks for completing your profile. We\'ve received your application and our team is reviewing it now. We\'ll let you know the moment your account is approved - no action needed from you.',
            self::ProfileRejected => sprintf(
                'Your account application needs changes before we can approve it. Reason: %s. Please update the flagged fields and resubmit.',
                $context['reason'] ?? 'See your profile for details',
            ),
            self::ProfileApproved => 'Your wallet account has been approved. You can now transfer money, pay bills, and use all wallet features.',
            self::ProfileChangeRequestApproved => 'Your requested profile changes have been approved and applied to your account.',
            self::ProfileChangeRequestRejected => sprintf(
                'Your profile update request was not approved.%s Please contact support if you need assistance.',
                isset($context['note']) && $context['note'] !== ''
                    ? ' Note: '.$context['note'].'.'
                    : '',
            ),
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
            self::BillPayment => sprintf(
                'You paid %s %s for %s.',
                $this->formatAmount($context['amount'] ?? 0),
                $context['currency'] ?? 'SDG',
                $context['service_name'] ?? 'a bill',
            ),
        };
    }

    private function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
