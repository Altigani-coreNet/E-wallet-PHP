<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Feature;
use App\Models\PlanScope;
use App\Models\PlanPrice;
use App\Models\Currency;
use App\Models\Country;
use App\Enums\RecurringType;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get currencies
        $aedCurrency = Currency::where('currency_code->en', 'AED')->first();
        $sdgCurrency = Currency::where('currency_code->en', 'SDG')->first();
        
        if (!$aedCurrency) {
            $this->command->warn('AED currency not found. Please run CurrencySeeder first.');
            return;
        }
        
        if (!$sdgCurrency) {
            $this->command->warn('SDG currency not found. Please run CurrencySeeder first.');
            return;
        }

        // Get countries
        $uaeCountry = Country::where('short_name', 'AE')
            ->orWhere('code', 'AE')
            ->orWhere('short_name', 'UAE')
            ->first();
            
        $sudanCountry = Country::where('short_name', 'SD')
            ->orWhere('code', 'SD')
            ->orWhere('short_name', 'SUDAN')
            ->first();
        
        if (!$uaeCountry) {
            $this->command->warn('UAE country not found. Please run CountrySeeder first.');
            return;
        }
        
        if (!$sudanCountry) {
            $this->command->warn('Sudan country not found. Please run CountrySeeder first.');
            return;
        }

        // Free Plan - Essentials only with limitations
        $freePlan = Plan::create([
            'name' => 'Free',
            'description' => 'Perfect for getting started with essential features. Ideal for small businesses testing the platform.',
            'price' => 0.00,
            'plan_type' => RecurringType::Monthly,
            'current_price' => null,
            'has_discount' => false,
            'status' => true,
        ]);

        // Free Plan Features
        $freeFeatures = [
            ['name' => 'Basic POS Operations', 'is_enabled' => true],
            ['name' => 'Basic Reporting', 'is_enabled' => true],
            ['name' => 'Email Support', 'is_enabled' => true],
        ];

        foreach ($freeFeatures as $feature) {
            Feature::create([
                'plan_id' => $freePlan->id,
                'name' => $feature['name'],
                'is_enabled' => $feature['is_enabled'],
            ]);
        }

        // Free Plan Scopes - Essentials only with strict limitations
        $freeScopes = [
            // POS Module
            ['scope_type' => 'users', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 1],
            ['scope_type' => 'terminals', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 1],
            ['scope_type' => 'transactions', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 100],
            ['scope_type' => 'batches', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 10],
            ['scope_type' => 'settlements', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 5],
            ['scope_type' => 'payment_links', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 10],
            // Cashier Module
            ['scope_type' => 'branches', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 1],
            ['scope_type' => 'categories', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 5],
            ['scope_type' => 'products', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 50],
            ['scope_type' => 'customers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 20],
            ['scope_type' => 'suppliers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 5],
            ['scope_type' => 'purchases', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 50],
            ['scope_type' => 'sales', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 100],
        ];

        foreach ($freeScopes as $scope) {
            PlanScope::create([
                'plan_id' => $freePlan->id,
                'scope_type' => $scope['scope_type'],
                'module' => $scope['module'],
                'is_enabled' => $scope['is_enabled'],
                'max_count' => $scope['max_count'],
            ]);
        }

        // Free Plan Price (AED - UAE)
        PlanPrice::create([
            'plan_id' => $freePlan->id,
            'currency_id' => $aedCurrency->id,
            'country_id' => $uaeCountry->id,
            'price' => 0.00,
            'current_price' => null,
            'is_default' => true,
        ]);

        // Free Plan Price (SDG - Sudan)
        PlanPrice::create([
            'plan_id' => $freePlan->id,
            'currency_id' => $sdgCurrency->id,
            'country_id' => $sudanCountry->id,
            'price' => 0.00,
            'current_price' => null,
            'is_default' => false,
        ]);

        // Standard Plan - More modules with limitations
        $standardPlan = Plan::create([
            'name' => 'Standard',
            'description' => 'Great for growing businesses. Includes more modules and features with reasonable limits.',
            'price' => 110.00,
            'plan_type' => RecurringType::Monthly,
            'current_price' => null,
            'has_discount' => false,
            'status' => true,
        ]);

        // Standard Plan Features
        $standardFeatures = [
            ['name' => '5 Concurrent Users | 2 Branch Support', 'is_enabled' => true],
            ['name' => 'Basic 15+ Payment Methods', 'is_enabled' => true],
            ['name' => '30-Day Data Retention | Email Support', 'is_enabled' => true],
            ['name' => 'Standard API Access', 'is_enabled' => true],
            ['name' => 'Essential Features for Getting Started', 'is_enabled' => true],
        ];

        foreach ($standardFeatures as $feature) {
            Feature::create([
                'plan_id' => $standardPlan->id,
                'name' => $feature['name'],
                'is_enabled' => $feature['is_enabled'],
            ]);
        }

        // Standard Plan Scopes - More capacity than Free
        $standardScopes = [
            // POS Module
            ['scope_type' => 'users', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 5],
            ['scope_type' => 'terminals', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 5],
            ['scope_type' => 'transactions', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 1000],
            ['scope_type' => 'batches', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 100],
            ['scope_type' => 'settlements', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 50],
            ['scope_type' => 'payment_links', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 100],
            // Cashier Module
            ['scope_type' => 'branches', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 2],
            ['scope_type' => 'categories', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 20],
            ['scope_type' => 'products', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 500],
            ['scope_type' => 'customers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 200],
            ['scope_type' => 'suppliers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 20],
            ['scope_type' => 'purchases', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 500],
            ['scope_type' => 'sales', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 1000],
        ];

        foreach ($standardScopes as $scope) {
            PlanScope::create([
                'plan_id' => $standardPlan->id,
                'scope_type' => $scope['scope_type'],
                'module' => $scope['module'],
                'is_enabled' => $scope['is_enabled'],
                'max_count' => $scope['max_count'],
            ]);
        }

        // Standard Plan Price (AED - UAE)
        PlanPrice::create([
            'plan_id' => $standardPlan->id,
            'currency_id' => $aedCurrency->id,
            'country_id' => $uaeCountry->id,
            'price' => 110.00,
            'current_price' => null,
            'is_default' => true,
        ]);

        // Standard Plan Price (SDG - Sudan)
        PlanPrice::create([
            'plan_id' => $standardPlan->id,
            'currency_id' => $sdgCurrency->id,
            'country_id' => $sudanCountry->id,
            'price' => 110.00,
            'current_price' => null,
            'is_default' => false,
        ]);

        // Business Plan - More capacity and features than Standard
        $businessPlan = Plan::create([
            'name' => 'Business',
            'description' => 'Complete suite for growing business. Includes advanced features, unlimited branches, and priority support.',
            'price' => 184.00,
            'plan_type' => RecurringType::Monthly,
            'current_price' => null,
            'has_discount' => false,
            'status' => true,
        ]);

        // Business Plan Features
        $businessFeatures = [
            ['name' => '20 Concurrent Users | Unlimited Branches', 'is_enabled' => true],
            ['name' => 'Advanced 20+ Payment Methods', 'is_enabled' => true],
            ['name' => '90-Day Data Retention | 24/5 Phone Support', 'is_enabled' => true],
            ['name' => 'Predictive Analytics | Full API Access', 'is_enabled' => true],
            ['name' => 'Complete Suite for Growing Business', 'is_enabled' => true],
        ];

        foreach ($businessFeatures as $feature) {
            Feature::create([
                'plan_id' => $businessPlan->id,
                'name' => $feature['name'],
                'is_enabled' => $feature['is_enabled'],
            ]);
        }

        // Business Plan Scopes - More capacity than Standard
        $businessScopes = [
            // POS Module
            ['scope_type' => 'users', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 20],
            ['scope_type' => 'terminals', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 20],
            ['scope_type' => 'transactions', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 5000],
            ['scope_type' => 'batches', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 500],
            ['scope_type' => 'settlements', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 200],
            ['scope_type' => 'payment_links', 'module' => 'pos', 'is_enabled' => true, 'max_count' => 500],
            // Cashier Module
            ['scope_type' => 'branches', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'categories', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 50],
            ['scope_type' => 'products', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 2000],
            ['scope_type' => 'customers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 1000],
            ['scope_type' => 'suppliers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 50],
            ['scope_type' => 'purchases', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 2000],
            ['scope_type' => 'sales', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => 5000],
        ];

        foreach ($businessScopes as $scope) {
            PlanScope::create([
                'plan_id' => $businessPlan->id,
                'scope_type' => $scope['scope_type'],
                'module' => $scope['module'],
                'is_enabled' => $scope['is_enabled'],
                'max_count' => $scope['max_count'],
            ]);
        }

        // Business Plan Price (AED - UAE)
        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'currency_id' => $aedCurrency->id,
            'country_id' => $uaeCountry->id,
            'price' => 184.00,
            'current_price' => null,
            'is_default' => true,
        ]);

        // Business Plan Price (SDG - Sudan)
        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'currency_id' => $sdgCurrency->id,
            'country_id' => $sudanCountry->id,
            'price' => 184.00,
            'current_price' => null,
            'is_default' => false,
        ]);

        // Enterprise Plan - All unlimited and more features
        $enterprisePlan = Plan::create([
            'name' => 'Enterprise',
            'description' => 'The ultimate plan for large enterprises. Everything unlimited with all premium features, white-label options, and dedicated support.',
            'price' => 367.00,
            'plan_type' => RecurringType::Monthly,
            'current_price' => null,
            'has_discount' => false,
            'status' => true,
        ]);

        // Enterprise Plan Features - All features enabled
        $enterpriseFeatures = [
            ['name' => 'Unlimited Users | Unlimited Branches', 'is_enabled' => true],
            ['name' => '50+ All Payment Methods', 'is_enabled' => true],
            ['name' => '12+ Months Data Retention | 24/7 Support', 'is_enabled' => true],
            ['name' => 'Advanced AI Analytics | White-Label', 'is_enabled' => true],
            ['name' => '100% SLA Guarantee | Custom Solutions', 'is_enabled' => true],
        ];

        foreach ($enterpriseFeatures as $feature) {
            Feature::create([
                'plan_id' => $enterprisePlan->id,
                'name' => $feature['name'],
                'is_enabled' => $feature['is_enabled'],
            ]);
        }

        // Enterprise Plan Scopes - All unlimited (max_count = null)
        $enterpriseScopes = [
            // POS Module
            ['scope_type' => 'users', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'terminals', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'transactions', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'batches', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'settlements', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'payment_links', 'module' => 'pos', 'is_enabled' => true, 'max_count' => null],
            // Cashier Module
            ['scope_type' => 'branches', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'categories', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'products', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'customers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'suppliers', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'purchases', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
            ['scope_type' => 'sales', 'module' => 'cashier', 'is_enabled' => true, 'max_count' => null],
        ];

        foreach ($enterpriseScopes as $scope) {
            PlanScope::create([
                'plan_id' => $enterprisePlan->id,
                'scope_type' => $scope['scope_type'],
                'module' => $scope['module'],
                'is_enabled' => $scope['is_enabled'],
                'max_count' => $scope['max_count'],
            ]);
        }

        // Enterprise Plan Price (AED - UAE)
        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'currency_id' => $aedCurrency->id,
            'country_id' => $uaeCountry->id,
            'price' => 367.00,
            'current_price' => null,
            'is_default' => true,
        ]);

        // Enterprise Plan Price (SDG - Sudan)
        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'currency_id' => $sdgCurrency->id,
            'country_id' => $sudanCountry->id,
            'price' => 367.00,
            'current_price' => null,
            'is_default' => false,
        ]);

        $this->command->info('================================================================================');
        $this->command->info('Plans seeded successfully!');
        $this->command->info('================================================================================');
        $this->command->info('Created Plans:');
        $this->command->info('  1. Free - 0 AED/month (Essentials with limitations)');
        $this->command->info('  2. Standard - 110 AED/month (5 Concurrent Users | 2 Branch Support)');
        $this->command->info('  3. Business - 184 AED/month (20 Concurrent Users | Unlimited Branches)');
        $this->command->info('  4. Enterprise - 367 AED/month (Unlimited Users | All Premium Features)');
        $this->command->info('================================================================================');
    }
}
