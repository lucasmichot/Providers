<?php

namespace SocialiteProviders\Zendesk;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ZENDESK';

    protected $scopes = ['read'];

    public static function additionalConfigKeys(): array
    {
        return ['subdomain'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://'.$this->getSubdomain().'.zendesk.com/oauth/authorizations/new', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://'.$this->getSubdomain().'.zendesk.com/oauth/tokens';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://'.$this->getSubdomain().'.zendesk.com/api/v2/users/me.json',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true)['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'    => $user['id'], 'nickname' => null, 'name' => $user['name'],
            'email' => $user['email'], 'avatar' => null,
        ]);
    }

    /**
     * Load the specified subdomain.
     *
     * @return string
     */
    protected function getSubdomain()
    {
        return $this->getConfig('subdomain');
    }
}
