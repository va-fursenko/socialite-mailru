<?php

namespace Jhaoda\SocialiteProviders\MailRu;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'MAILRU';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://connect.mail.ru/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.mail.ru/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $params = [
            'secure'      => 1,
            'format'      => 'json',
            'method'      => 'users.getInfo',
            'app_id'      => $this->clientId,
            'session_key' => $token
        ];

        ksort($params);

        $_params = array_map(function($key, $value) {
            return $key . '=' . $value;
        }, array_keys($params), array_values($params));

        $params['sig'] = md5(join('', $_params) . $this->clientSecret);

        $response = $this->getHttpClient()->get(
            'http://www.appsmail.ru/platform/api?' . http_build_query($params)
        );

        return json_decode($response->getBody(), true)[0];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['uid'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'email'    => array_get($user, 'email'),
            'nickname' => array_reverse(explode('/', $user['link']))[1],
            'avatar'   => $user['has_pic'] ? $user['pic_190'] : null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
