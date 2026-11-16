<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Atrium\Admin\Support\ContentLanguages;
use Illuminate\Contracts\View\View;

/**
 * A public article page in every content language: /articles/{code} for the
 * default one, /de/articles/{code} for the others (see routes/web.php).
 */
class ArticleController extends Controller
{
    public function show(ContentLanguages $languages, string $code): View
    {
        $locale = app()->getLocale();

        $article = Article::query()
            ->where('active', true)
            ->where("code->{$locale}", $code)
            ->firstOrFail();

        // Alternate addresses only for the languages the article is written in.
        $urls = [];

        foreach ($languages->codes() as $language) {
            $translated = $article->getTranslation('code', $language, false);

            if ($translated !== null && $translated !== '' && $article->getTranslation('name', $language, false)) {
                $urls[$language] = $languages->route('articles.show', ['code' => $translated], $language);
            }
        }

        return view('articles.show', ['article' => $article, 'urls' => $urls]);
    }
}
