<?php

namespace App\Support;

/**
 * System chart-of-account codes for e-wallet double-entry postings.
 */
final class AccountCode
{
    public const BANK = 1000;

    public const EWALLET_FLOAT = 1010;

    public const CUSTOMER_LIABILITY = 2000;

    public const MASTER_LIABILITY = 2050;

    /** Control account — not used for postings; per-partner payables use 2110–2199. */
    public const PROVIDER_PAYABLE_CONTROL = 2100;

    public const PROVIDER_PAYABLE_MIN = 2110;

    public const PROVIDER_PAYABLE_MAX = 2199;

    public const FEES_TAX_PAYABLE = 2900;

    public const OWNER_EQUITY = 3000;

    public const RETAINED_EARNINGS = 3900;

    public const FEE_INCOME = 4000;
}
