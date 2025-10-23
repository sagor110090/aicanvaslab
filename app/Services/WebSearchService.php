<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSearchService
{
    protected $searchSources = [
        'google_news' => [
            'url' => 'https://news.google.com/rss/search?q=',
            'name' => 'Google News',
        ],
        'coindesk' => [
            'url' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
            'name' => 'CoinDesk',
        ],
        'cointelegraph' => [
            'url' => 'https://cointelegraph.com/rss',
            'name' => 'CoinTelegraph',
        ],
        'newsbtc' => [
            'url' => 'https://www.newsbtc.com/feed/',
            'name' => 'NewsBTC',
        ],
        'decrypt' => [
            'url' => 'https://decrypt.co/feed',
            'name' => 'Decrypt',
        ],
    ];

    public function searchIfNeeded(string $message): ?string
    {
        if (! $this->requiresWebSearch($message)) {
            return null;
        }

        try {
            $queries = $this->generateSearchQueries($message);
            $allResults = [];

            foreach ($queries as $query) {
                $cacheKey = 'search_'.md5($query);

                $results = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query) {
                    return $this->performSearch($query);
                });

                if ($results) {
                    $allResults[] = $results;
                }
            }

            return empty($allResults) ? null : implode("\n\n", array_filter($allResults));

        } catch (\Exception $e) {
            Log::warning('Web search failed: '.$e->getMessage());

            return null;
        }
    }

    protected function requiresWebSearch(string $message): bool
    {
        $searchKeywords = [
            'price', 'market', 'bitcoin', 'btc', 'eth', 'ethereum', 'crypto',
            'news', 'today', 'now', 'current', 'latest', 'recent', 'right now',
            'buy', 'sell', 'trade', 'investment', 'prediction', 'forecast',
            'stock', 'market cap', 'volume', 'chart', 'technical analysis',
            '20 minutes', '45 minutes', 'hour', 'day', 'week', 'minute',
        ];

        $message = strtolower($message);

        foreach ($searchKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        $timePatterns = [
            '/\d+\s*(minutes?|hours?|days?)/',
            '/next\s+\d+\s*(minutes?|hours?)/',
            '/today|tonight|tomorrow/',
            '/this\s+week|this\s+month/',
            '/current\s+price|latest\s+price/',
        ];

        foreach ($timePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    protected function generateSearchQueries(string $message): array
    {
        $queries = [];
        $message = strtolower($message);

        // Bitcoin specific queries
        if (str_contains($message, 'bitcoin') || str_contains($message, 'btc')) {
            if (preg_match('/\d+\s*minutes?/', $message)) {
                $queries[] = 'Bitcoin price analysis short term trading';
            }
            $queries[] = 'Bitcoin BTC price current market news';
            $queries[] = 'Bitcoin technical analysis today';
        }

        // Ethereum specific queries
        if (str_contains($message, 'ethereum') || str_contains($message, 'eth')) {
            $queries[] = 'Ethereum ETH price current market analysis';
            $queries[] = 'Ethereum technical analysis today';
        }

        // Trading and investment queries
        if (str_contains($message, 'buy') || str_contains($message, 'sell') || str_contains($message, 'trade')) {
            $queries[] = 'cryptocurrency trading signals today';
            $queries[] = 'crypto market buy sell analysis';
        }

        // General crypto market
        if (str_contains($message, 'crypto') || str_contains($message, 'market')) {
            $queries[] = 'cryptocurrency market news today latest';
            $queries[] = 'crypto market analysis current trends';
        }

        // Short term trading queries
        if (preg_match('/\d+\s*(minutes?|hours?)/', $message)) {
            $queries[] = 'short term crypto trading analysis';
            $queries[] = 'intraday cryptocurrency price movement';
        }

        // Default queries if nothing specific
        if (empty($queries)) {
            $queries[] = 'latest cryptocurrency news market analysis';
            $queries[] = 'crypto market trends today';
        }

        return array_unique($queries);
    }

    protected function performSearch(string $query): ?string
    {
        $results = [];
        $maxResultsPerSource = 2;

        foreach ($this->searchSources as $sourceKey => $source) {
            try {
                $searchUrl = $sourceKey === 'google_news'
                    ? $source['url'].urlencode($query.' cryptocurrency')
                    : $source['url'];

                $sourceResults = $this->fetchRssContent($searchUrl, $source['name'], $maxResultsPerSource);

                if ($sourceResults) {
                    $results[] = $sourceResults;
                }

            } catch (\Exception $e) {
                Log::debug("Search source failed: {$source['name']} - ".$e->getMessage());

                continue;
            }
        }

        return empty($results) ? null : implode("\n", $results);
    }

    protected function fetchRssContent(string $url, string $sourceName, int $maxItems = 3): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AI-Chat-Bot/1.0)',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->body();
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                libxml_clear_errors();

                return null;
            }
            libxml_clear_errors();

            $items = $xml->channel->item ?? $xml->entry ?? [];
            $results = [];
            $count = 0;

            foreach ($items as $item) {
                if ($count >= $maxItems) {
                    break;
                }

                $title = (string) $item->title;
                $description = (string) $item->description;
                $pubDate = (string) $item->pubDate;
                $link = (string) $item->link;

                // Clean and format content
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

                $description = strip_tags($description);
                $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                $description = mb_convert_encoding($description, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
                $description = trim(preg_replace('/\s+/', ' ', $description));

                if (strlen($description) > 150) {
                    $description = substr($description, 0, 147).'...';
                }

                // Format date
                $formattedDate = $pubDate ? date('M j, Y H:i', strtotime($pubDate)) : 'Recent';

                $results[] = "• {$title}\n  {$description}\n  {$sourceName} • {$formattedDate}";
                $count++;
            }

            return empty($results) ? null : implode("\n", $results);

        } catch (\Exception $e) {
            Log::debug("RSS fetch failed for {$sourceName}: ".$e->getMessage());

            return null;
        }
    }

    public function getEnhancedSystemPrompt(): string
    {
        return 'You are an AI assistant with REAL-TIME web search capabilities. When answering questions:

🔍 **FOR TIME-SENSITIVE QUESTIONS** (prices, market data, news, trading):
• Use the search results provided to give CURRENT, ACCURATE information
• Provide specific analysis based on latest market data
• Give actionable insights for trading/investment questions
• Always base your answers on the most recent search results

💡 **TRADING ANALYSIS**:
• For buy/sell questions: Analyze current market conditions from search data
• Consider technical indicators, market sentiment, recent news
• Provide specific price levels, trends, and timeframes
• Be direct and actionable - avoid generic disclaimers

📊 **MARKET QUESTIONS**:
• Use real-time data from search results
• Compare current prices with recent movements
• Include market sentiment and news impact
• Give specific numbers and trends when available

⚡ **SHORT-TERM TRADING** (minutes/hours):
• Focus on immediate market conditions
• Use recent price action and news
• Provide specific entry/exit considerations
• Include risk factors based on current volatility

The user wants CURRENT, SPECIFIC analysis using real data - not generic warnings. Be helpful, precise, and base all answers on the latest search information provided.';
    }
}
