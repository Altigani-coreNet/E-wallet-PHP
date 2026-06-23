<?php

namespace App\Providers;

use App\Repositories\BatchRepository;
use App\Repositories\BrandRepository;
use App\Repositories\BranchRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UnitRepository;
use App\Services\BrandService;
use App\Services\BranchService;
use App\Services\CategoryService;
use App\Services\UnitService;
use App\View\Composers\EmailLocaleViewComposer;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Repositories\PaymentByLinkRepository;
use App\Repositories\PlanRepository;
use App\Repositories\SMSRepository;
use App\Services\BatchService;
use App\Services\PaymentByLinkService;
use App\Services\PlanService;
use App\Services\SMSService;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ViewFacade::composer('*', function (View $view) {
            $name = $view->name();
            if (is_string($name) && str_starts_with($name, 'emails.')) {
                (new EmailLocaleViewComposer())->compose($view);
            }
        });

        // Configure Passport to support multiple authentication guards (User and Admin)
        // This allows both User and Admin models to use createToken() method
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        
        $this->app->bind(UserService::class, UserRepository::class);
        $this->app->bind(BatchService::class, BatchRepository::class);
        // $this->app->bind(BranchService::class, BranchRepository::class);

        $this->app->bind(CategoryService::class, CategoryRepository::class);
        $this->app->bind(BrandService::class, BrandRepository::class);
        $this->app->bind(UnitService::class, UnitRepository::class);
        $this->app->bind(PlanService::class, PlanRepository::class);
        $this->app->bind(PaymentByLinkService::class, PaymentByLinkRepository::class);
        // $this->app->bind(SMSService::class, SMSRepository::class);
    }
}
