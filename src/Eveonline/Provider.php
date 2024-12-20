<?php

namespace SocialiteProviders\Eveonline;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use UnexpectedValueException;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'EVEONLINE';

    /**
     * Tranquility endpoint for retrieving user info.
     */
    public const TRANQUILITY_ENDPOINT = 'https://login.eveonline.com';

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.eveonline.com/v2/oauth/authorize/', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.eveonline.com/v2/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $authorization = 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret);

        $response = $this->getHttpClient()->post('https://login.eveonline.com/v2/oauth/token', [
            RequestOptions::HEADERS => [
                'Authorization' => $authorization,
            ],
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'authorization_code',
                'code'       => $code,
            ],
        ]);

        // Values are access_token // expires_in // token_type // refresh_token
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return $this->verify($token);
    }

    /**
     * @param  string  $jwt
     * @return array
     *
     * @throws \UnexpectedValueException|\Firebase\JWT\ExpiredException
     */
    public function verify($jwt)
    {
        $responseJwks = $this->getHttpClient()->get('https://login.eveonline.com/oauth/jwks');
        $responseJwksInfo = json_decode((string) $responseJwks->getBody(), true);
        $decodedArray = (array) JWT::decode($jwt, JWK::parseKeySet($responseJwksInfo));

        if ($decodedArray['iss'] !== 'login.eveonline.com' && $decodedArray['iss'] !== self::TRANQUILITY_ENDPOINT) {
            throw new UnexpectedValueException('Access token issuer mismatch');
        }

        if ($decodedArray['exp'] <= time()) {
            throw new ExpiredException;
        }

        return $decodedArray;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'character_owner_hash' => $user['owner'],
            'character_name'       => $user['name'],
            'character_id'         => ltrim($user['sub'], 'CHARACTER:EVE:'),
        ]);
    }
}
