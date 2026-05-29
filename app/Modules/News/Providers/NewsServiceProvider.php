<?php

namespace App\Modules\News\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\News\Interfaces\NewsCommentRepositoryInterface;
use App\Modules\News\Interfaces\NewsLikeRepositoryInterface;
use App\Modules\News\Interfaces\NewsRepositoryInterface;
use App\Modules\News\Interfaces\NewsServiceInterface;
use App\Modules\News\Repositories\NewsCommentRepository;
use App\Modules\News\Repositories\NewsLikeRepository;
use App\Modules\News\Repositories\NewsRepository;
use App\Modules\News\Services\NewsService;

class NewsServiceProvider extends BaseModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsRepositoryInterface::class, NewsRepository::class);
        $this->app->singleton(NewsLikeRepositoryInterface::class, NewsLikeRepository::class);
        $this->app->singleton(NewsCommentRepositoryInterface::class, NewsCommentRepository::class);
        $this->app->singleton(\App\Modules\News\Interfaces\NewsServiceInterface::class, \App\Modules\News\Services\NewsService::class);
        $this->app->singleton(\App\Modules\News\Interfaces\AdminNewsServiceInterface::class, \App\Modules\News\Services\AdminNewsService::class);
    }

    protected function getModuleName(): string
    {
        return 'News';
    }
}
