<?php

declare(strict_types=1);

namespace Sendportal\Base\Adapters;

use Sendportal\Base\Services\Messages\MessageTrackingOptions;
use Aws\Result;
use Aws\Ses\SesClient;
use Illuminate\Contracts\Container\BindingResolutionException;

class SesMailAdapter extends BaseMailAdapter
{
    /** @var SesClient */
    protected $client;

    /**
     * @throws BindingResolutionException
     */
    public function send(string $fromEmail, string $toEmail, string $subject, MessageTrackingOptions $trackingOptions, string $content): ?string
    {
        // TODO(david): It isn't clear whether it is possible to set per-message tracking for SES.

        $result = $this->resolveClient()->sendEmail([
            'Source' => $fromEmail,

            'Destination' => [
                'ToAddresses' => [$toEmail],
            ],

            'Message' => [
                'Subject' => [
                    'Data' => $subject,
                ],
                'Body' => array(
                    'Html' => [
                        'Data' => $content,
                    ],
                ),
            ],
            'ConfigurationSetName' => \Arr::get($this->config, 'configuration_set_name'),
        ]);

        return $this->resolveMessageId($result);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function resolveClient(): SesClient
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = app()->make('aws')->createClient('ses', [
            'region' => \Arr::get($this->config, 'region'),
            'credentials' => [
                'key' => \Arr::get($this->config, 'key'),
                'secret' => \Arr::get($this->config, 'secret'),
            ]
        ]);

        return $this->client;
    }

    protected function resolveMessageId(Result $result): string
    {
        return \Arr::get($result->toArray(), 'MessageId');
    }
}