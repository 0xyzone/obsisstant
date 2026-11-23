<?php

namespace App\Http\Controllers;

use App\Services\ChangelogService;
use App\Services\KnowledgeBaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function __invoke(Request $request): View
    {
        $articles = KnowledgeBaseService::getArticles();
        $requestedSlug = trim((string) $request->query('topic', ''));
        $activeArticle = $requestedSlug !== ''
            ? KnowledgeBaseService::findArticle($requestedSlug)
            : null;

        return view('knowledge-base', [
            'currentVersion' => ChangelogService::getCurrentVersion(),
            'articles' => $articles,
            'activeSlug' => $activeArticle['slug'] ?? ($articles[0]['slug'] ?? ''),
            'commandReference' => KnowledgeBaseService::getCommandReference(),
        ]);
    }
}
