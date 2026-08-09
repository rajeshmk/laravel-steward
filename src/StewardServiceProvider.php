<?php

declare(strict_types=1);

namespace Hatchyu\Steward;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\Parsers\AutoQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Hatchyu\Steward\Queries\Processors\DefaultQueryParamsProcessor;
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
            QueryParamsParserContract::class,
            function (): QueryParamsParserContract {
                $defaultSize = (int) config('steward.default_page_size', 15);
                $maxSize = (int) config('steward.max_page_size', 100);

                return new AutoQueryParamsParser(
                    defaultParser: new DefaultQueryParamsParser($defaultSize, $maxSize),
                    jsonApiParser: new JsonApiQueryParamsParser($defaultSize, $maxSize),
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
