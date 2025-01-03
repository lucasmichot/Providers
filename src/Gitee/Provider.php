<?php

namespace SocialiteProviders\Gitee;

use Exception;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GITEE';

    protected $scopes = ['user_info', 'emails'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://gitee.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://gitee.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://gitee.com/api/v5/user', $this->getRequestOptions($token));

        $user = json_decode((string) $response->getBody(), true);

        if (in_array('emails', $this->scopes, true)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = 'https://gitee.com/api/v5/emails';

        try {
            $response = $this->getHttpClient()->get(
                $emailsUrl,
                $this->getRequestOptions($token)
            );
        } catch (Exception) {
            return null;
        }

        foreach (json_decode((string) $response->getBody(), true) as $email) {
            if ($email['state'] === 'confirmed' && in_array('primary', $email['scope'], true)) {
                return $email['email'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['name'],
            'name'     => $user['login'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar_url'],
        ]);
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @param  string  $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            RequestOptions::QUERY => [
                'access_token' => $token,
            ],
        ];
    }
}
