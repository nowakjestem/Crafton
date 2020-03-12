<?php


namespace App\Services;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GithubService
{
    const ENDPOINTS_MAP = [
        'List teams' => [
            'uri' => '/orgs/:org/teams',
            'method' => 'GET',
        ],
        'Get team by name' => [
            'uri' => '/orgs/:org/teams/:team_slug',
            'method' => 'GET',
        ],
        'Create team' => [
            'uri' => '/orgs/:org/teams',
            'method' => 'POST',
            'arguments' => [
                'name' => 'required',
                'description' => 'string',
                'maintainers' => 'array',
                'repo_names' => 'array',
                'privacy' => 'in:secret,closed',
                'parent_team_id' => 'number',
            ],
        ],
        'Search repositories' => [
            'uri' => 'search/repositories',
            'method' => 'GET',
            'arguments' => [
                'q' => 'required',
                'sort' => 'string',
                'order' => 'in:desc,asc',
            ],
        ],
    ];

    public function call(string $endpointName, array $linkParams = [], array $bodyParams = [])
    {
        $endpoint = self::ENDPOINTS_MAP[$endpointName];
        $uri = Arr::get($endpoint, 'uri');
        foreach ($linkParams as $key => $value) {
            str_replace($key, $value, $uri);
        }
        $fullUri = config('github.api_url') . $uri;
        $method = Arr::get($endpoint, 'method');
        if (strtoupper($method) === 'GET') {
            $fullUri .= '?' . http_build_query($bodyParams);
        }
        return Http::send($method, $fullUri, $bodyParams);
    }
}
