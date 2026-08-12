<?php

declare(strict_types=1);

namespace Hatchyu\Steward;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\Parsers\AutoQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Hatchyu\Steward\Queries\Processors\DefaultQueryParamsProcessor;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Illuminate\Support\ServiceProvider;
use Override;

final class StewardServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/steward.php',
            'steward'
        );
        $this->app->singleton(
            QueryParamLimits::class,
            static fn (): QueryParamLimits => QueryParamLimits::fromConfig(),
        );
        $this->app->singleton(
            QueryParamsParserContract::class,
            function (): QueryParamsParserContract {
                $limits = resolve(QueryParamLimits::class);
                return new AutoQueryParamsParser(
                    defaultParser: new DefaultQueryParamsParser($limits),
                    jsonApiParser: new JsonApiQueryParamsParser($limits),
                );
            }
        );

        $this->app->singleton(
            QueryParamsProcessorContract::class,
            static fn (): QueryParamsProcessorContract => new DefaultQueryParamsProcessor()
        );
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/steward.php' => config_path('steward.php'),
            ],
            'steward-config'
        );
    }
}
