<?php

namespace SocialiteProviders\Live;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LIVE';

    protected $scopes = ['User.Read User.ReadBasic.All'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            [RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => $user['displayName'],
            'email'    => $user['userPrincipalName'],
            'avatar'   => null,
        ]);
    }
}
