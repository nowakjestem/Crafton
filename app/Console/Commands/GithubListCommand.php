<?php

namespace App\Console\Commands;

use App\Services\GithubService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class GithubListCommand extends Command
{
    const PREVIOUS_PAGE_OPTION = 9;
    const NEXT_PAGE_OPTION = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all github endpoints';

    /**
     * @var int
     */
    protected $currentPage = 0;

    /**
     * @var array
     */
    protected $availableCommands;

    /**
     * @var boolean
     */
    protected $finished = false;
    /**
     * @var GithubService
     */
    private $githubService;


    public function __construct(GithubService $githubService)
    {
        $this->availableCommands = array_keys(GithubService::ENDPOINTS_MAP);
        $this->githubService = $githubService;
        parent::__construct();
    }

    public function handle()
    {
        while ($this->finished === false) {
            $this->line('Choose endpoint to see details about:');
            foreach ($this->getCurrentCommands() as $index => $command) {
                $this->line(sprintf('[%s] %s', $index + 1, $command));
            }
            if ($this->currentPage > 0) {
                $this->line(sprintf('[%s] Previous page', self::PREVIOUS_PAGE_OPTION));
            }
            $this->line(sprintf('[%s] Next page', self::NEXT_PAGE_OPTION));
            $chosen = $this->ask('Choose option');
            $this->handleSelection((int)$chosen);
        }
    }

    protected function handleSelection(int $chosen): void
    {
        switch ($chosen) {
            case self::PREVIOUS_PAGE_OPTION:
                $this->currentPage--;
                break;
            case self::NEXT_PAGE_OPTION:
                $this->currentPage++;
                break;
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
                $this->chooseOption($chosen);
                break;
            default:
                $this->error('Wrong option selected.');
        }
    }

    protected function getCurrentCommands(): array
    {
        return array_slice($this->availableCommands, $this->currentPage * 8, 8);
    }

    protected function chooseOption(int $chosen): void
    {
        $realIndex = $this->currentPage * 8 + $chosen - 1;
        $endpointName = $this->availableCommands[$realIndex];
        $endpoint = Arr::get(GithubService::ENDPOINTS_MAP, $endpointName);
        $this->line('Uri: ' . Arr::get($endpoint, 'uri'));
        $this->line('Method: ' . Arr::get($endpoint, 'method'));
        if (Arr::has($endpoint, 'arguments')) {
            $this->line('Arguments: ');
            foreach (Arr::get($endpoint, 'arguments') as $argument => $rules) {
                $this->line(sprintf('%s -  validation rules: %s', $argument, $rules));
            }
        }
        $this->handleOptionCall($endpointName);
        if ($this->confirm('Finish?')) {
            $this->finished = true;
        }
    }

    protected function handleOptionCall(string $endpointName): void
    {
        $endpoint =  Arr::get(GithubService::ENDPOINTS_MAP, $endpointName);
        $linkParamKeys = [];
        preg_match_all('/:([a-z])+/i', Arr::get($endpoint, 'uri'), $linkParamKeys);
        $linkParams = [];
        foreach ($linkParamKeys[0] as $linkParamKey) {
            $linkParams[$linkParamKey] = $this->ask(sprintf('Enter "%s"', $linkParamKey));
        }

        $argumentValues = [];
        foreach (Arr::get($endpoint, 'arguments', []) as $argument => $rules) {
            $argumentValues[$argument] = $this->ask(sprintf('Enter %s (rules: %s):', $argument, $rules));
        }

        $this->line($this->githubService->call($endpointName, $linkParams, $argumentValues));
    }
}
