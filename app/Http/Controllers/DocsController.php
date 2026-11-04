<?php

namespace App\Http\Controllers;

use App\Docs\DocumentationRepository;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function show(?string $slug = null): View
    {
        $article = DocumentationRepository::find($slug);

        abort_if(! $article || ! view()->exists($article['view']), 404);

        $neighbors = DocumentationRepository::neighbors($article['slug']);

        return view('pages.docs.show', [
            'sections'  => DocumentationRepository::sections(),
            'article'   => $article,
            'prev'      => $neighbors['prev'],
            'next'      => $neighbors['next'],
        ]);
    }
}
