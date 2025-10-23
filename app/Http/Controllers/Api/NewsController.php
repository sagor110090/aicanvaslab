<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NewsController extends Controller
{
    protected $newsSources = [
        [
            'name' => 'CoinDesk',
            'url' => 'https://www.coindesk.com',
            'rss_url' => 'https://feeds.feedburner.com/coindesk',
        ],
        [
            'name' => 'CoinTelegraph',
            'url' => 'https://cointelegraph.com',
            'rss_url' => 'https://cointelegraph.com/rss',
        ],
        [
            'name' => 'NewsBTC',
            'url' => 'https://www.newsbtc.com',
            'rss_url' => 'https://www.newsbtc.com/feed/',
        ],
    ];

    public function getNews(Request $request)
    {
        $request->validate([
            'source' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $source = $request->input('source');
        $limit = $request->input('limit', 10);
        $cacheKey = 'crypto_news_'.($source ?: 'all').'_'.$limit;

        // Cache news for 15 minutes to avoid rate limiting
        $news = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($source, $limit) {
            $allNews = [];

            $sourcesToFetch = $source
                ? collect($this->newsSources)->firstWhere('name', $source)
                    ? [$this->newsSources[array_search($source, array_column($this->newsSources, 'name'))]]
                    : $this->newsSources
                : $this->newsSources;

            foreach ($sourcesToFetch as $newsSource) {
                try {
                    $newsItems = $this->fetchRssFeed($newsSource['rss_url'], $newsSource['name']);
                    $allNews = array_merge($allNews, $newsItems);
                } catch (\Exception $e) {
                    \Log::error("Failed to fetch news from {$newsSource['name']}: ".$e->getMessage());
                    // Continue with other sources even if one fails
                }
            }

            // Sort by date and limit results
            usort($allNews, function ($a, $b) {
                return strtotime($b['published_at']) - strtotime($a['published_at']);
            });

            return array_slice($allNews, 0, $limit);
        });

        return response()->json([
            'news' => $news,
            'sources' => array_column($this->newsSources, 'name'),
            'cached_until' => now()->addMinutes(15)->toISOString(),
            'total_count' => count($news),
        ]);
    }

    protected function fetchRssFeed($rssUrl, $sourceName)
    {
        try {
            $response = Http::timeout(15)->get($rssUrl);

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch RSS feed: HTTP '.$response->status());
            }

            // Fix UTF-8 encoding issues
            $content = $response->body();
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

            // Disable libxml errors and handle them manually
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('Failed to parse XML: '.implode(', ', array_map(function ($error) {
                    return $error->message;
                }, $errors)));
            }
            libxml_clear_errors();

            $newsItems = [];
            $items = $xml->channel->item ?? $xml->entry ?? [];

            foreach ($items as $item) {
                try {
                    $title = (string) $item->title;
                    $description = (string) $item->description;
                    $link = (string) $item->link;
                    $pubDate = (string) $item->pubDate;
                    $publishedAt = $pubDate ? Carbon::parse($pubDate)->toDateTimeString() : now()->toDateTimeString();

                    // Clean up HTML from description and fix encoding
                    $cleanDescription = strip_tags($description);
                    $cleanDescription = mb_convert_encoding($cleanDescription, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
                    $cleanDescription = trim(preg_replace('/\s+/', ' ', $cleanDescription));

                    // Fix title encoding
                    $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

                    // Limit description length
                    if (strlen($cleanDescription) > 200) {
                        $cleanDescription = substr($cleanDescription, 0, 197).'...';
                    }

                    // Validate required fields
                    if (empty($title) || empty($link)) {
                        continue;
                    }

                    // Ensure all strings are valid UTF-8
                    $newsItems[] = [
                        'title' => $title,
                        'description' => $cleanDescription ?: 'No description available',
                        'url' => $link,
                        'source' => $sourceName,
                        'published_at' => $publishedAt,
                    ];

                    // Limit to 10 items per source for better coverage
                    if (count($newsItems) >= 10) {
                        break;
                    }
                } catch (\Exception $itemError) {
                    \Log::warning("Failed to process news item from {$sourceName}: ".$itemError->getMessage());

                    continue;
                }
            }

            return $newsItems;

        } catch (\Exception $e) {
            \Log::error("RSS fetch error for {$sourceName}: ".$e->getMessage());

            return [];
        }
    }
}
