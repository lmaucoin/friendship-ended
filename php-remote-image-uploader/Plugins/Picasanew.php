<?php
/**
 * You must have an api to use this.
 * Register an API in http://console.developers.google.com
 * -> APIs & auth -> Credentials -> OAuth API.
 *
 * @update May 27, 2015
 */
class ChipVN_ImageUploader_Plugins_Picasanew extends ChipVN_ImageUploader_Plugins_Abstract
{
    const OAUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/auth';
    const OAUTH_TOKEN_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/token';
    const OAUTH_SCOPE_PICASA = 'https://picasaweb.google.com/data/';

    const USER_FEED_ENDPOINT = 'https://picasaweb.google.com/data/feed/api/user/default';
    const ALBUM_FEED_ENPOINT = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/%s';

    const OAUTH_TOKEN = 'oauth_token';
    const OAUTH_TOKEN_EXPIRES_AT = '__expires_at';

    /**
     * Because Google's OAuth Refresh Token does not have expiration time
     * so we can set the token expiration date with a long value.
     * Here, we use 5 years for expiration. When access_token is expired, we
     * will refresh token to get new access_token.
     */
    const CACHE_TOKEN_TIME = 157680000;

    /**
     * Picasa album id.
     *
     * @var string
     */
    protected $albumId = 'default';

    /**
     * API secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Set API secret.
     *
     * @param string $secret
     *
     * @return void
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Set AlbumID.
     * You can set AlbumId by an array, then method will get random an id.
     *
     * @param string|array
     */
    public function setAlbumId($albumIds)
    {
        if (empty($albumIds)) {
            $albumIds = 'default';
        }
        $albumIds = (array) $albumIds;
        $this->albumId = $albumIds[array_rand($albumIds, 1)];
    }

    /**
     * Get auth token.
     * This method will direct user to google to authorize
     * after success, google will direct user back to App url.
     *
     * @param string $callback
     *
     * @return array
     */
    public function getOAuthToken($callback)
    {
        if (empty($_GET['code'])) {
            return $this->requestToken($callback);
        }

        return $this->getOAuthAccessToken($_GET['code'], $callback);
    }

    /**
     * Refresh token and update to cache.
     *
     * @param array $token
     *
     * @return void
     */
    public function refreshToken(array $token)
    {
        $client = $this->createHttpClient();
        $client->setParameters(array(
            'refresh_token' => $token['refresh_token'],
            'client_id'     => $this->apiKey,
            'client_secret' => $this->secret,
            'grant_type'    => 'refresh_token',
        ))
        ->execute(self::OAUTH_TOKEN_ENDPOINT, 'POST');

        $result = json_decode($client, true);

        if (isset($result['error'])) {
            $this->throwException('REFRESH_TOKEN_PROBLEM: %s -> %s', $result['error'], $result['error_description']);
        }

        $this->saveToken($result);
    }

    /**
     * Determine if token still valid.
     *
     * @return bool
     */
    public function hasValidToken()
    {
        $token = $this->getToken();

        return !empty($token) && !$this->isTokenExpired($token);
    }

    /**
     * Determine if token is expired.
     *
     * @param array $token
     *
     * @return bool
     */
    public function isTokenExpired(array $token)
    {
        return empty($token[self::OAUTH_TOKEN_EXPIRES_AT])
            || $token[self::OAUTH_TOKEN_EXPIRES_AT] < time();
    }

    /**
     * Gets current token.
     *
     * @return array
     */
    public function getToken()
    {
        return $this->getCache()->get(self::OAUTH_TOKEN, array());
    }

    /**
     * Sets token
     *
     * @param array $token
     */
    public function setToken(array $token)
    {
        $this->getCache()->set(self::OAUTH_TOKEN, $token, self::CACHE_TOKEN_TIME);
    }

    /**
     * Save token.
     *
     * @param array $token
     *
     * @return void
     */
    public function saveToken(array $token)
    {
        $token[self::OAUTH_TOKEN_EXPIRES_AT] = $token['expires_in'] + time();
        $token = array_merge($this->getToken(), $token);

        $this->getCache()->set(self::OAUTH_TOKEN, $token, self::CACHE_TOKEN_TIME);
    }

    /**
     * Exchange the authorization code for an access token
     * and a refresh token.
     *
     * @param string $code
     * @param string $callback redirect_uri
     *
     * @return array
     *
     * @throws Exception if get access token failed.
     */
    protected function getOAuthAccessToken($code, $callback)
    {
        $client = $this->createHttpClient();
        $client->setParameters(array(
            'code'          => $code,
            'client_id'     => $this->apiKey,
            'client_secret' => $this->secret,
            'redirect_uri'  => $callback,
            'grant_type'    => 'authorization_code',
        ))
        ->execute(self::OAUTH_TOKEN_ENDPOINT, 'POST');

        $result = json_decode($client, true);

        if (isset($result['error'])) {
            $this->throwException('GET_TOKEN_PROBLEM: %s -> %s', $result['error'], $result['error_description']);
        }
        $this->saveToken($result);

        return json_decode($client, true);
    }

    /**
     * Direct user to Google to get authorisation.
     *
     * @param string $callback redirect uri
     *
     * @return void
     */
    protected function requestToken($callback)
    {
        $url = $this->getRequestTokenUrl($callback);

        if (headers_sent()) {
            echo '<meta http-equiv="refresh" content="0; url='.$url.'">'
                .'<script type="text/javascript">window.location.href = "'.$url.'";</script>';
        } else {
            header('Location: '.$url);
        }
        exit;
    }

    /**
     * Get request token url.
     *
     * @param string $callback redirect_uri
     *
     * @return string
     */
    public function getRequestTokenUrl($callback)
    {
        $params = array(
            'response_type'          => 'code',
            'client_id'              => $this->apiKey,
            'redirect_uri'           => $callback,
            'scope'                  => self::OAUTH_SCOPE_PICASA,
            'state'                  => 'request_token',
            'approval_prompt'        => 'force',
            'access_type'            => 'offline',
            'include_granted_scopes' => 'true',
        );

        return self::OAUTH_ENDPOINT.'?'.http_build_query($params);
    }

    /**
     * With API version 2
     * {@link https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol}
     * its use OAuth 2.0, then doesn't authorize with username and password.
     * But we still need username for generate api endpoint, so need
     * call `login($username, null)` method to tell application that we use it.
     */
    protected function doLogin()
    {
        $this->username = preg_replace('#@gmail\.com#i', '', $this->username);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        $this->checkValidToken(__METHOD__);
        $endpoint = sprintf(self::ALBUM_FEED_ENPOINT, $this->albumId).'?alt=json';

        $this->resetHttpClient()
            ->setHeaders($this->getGeneralHeaders())
            ->setSubmitNormal('image/jpeg')
            ->setHeaders('Slug', basename($this->file))
            ->setRawPostFile($this->file)
        ->execute($endpoint);

        $this->checkHttpClientErrors(__METHOD__);
        $result = json_decode($this->client, true);

        if ($this->client->getResponseStatus() != 201
            || empty($result['entry']['media$group']['media$content'][0])
        ) {
            $this->throwException('%s: Upload failed. %s', __METHOD__, $this->client);
        }

        // url, width, height, type
        extract($result['entry']['media$group']['media$content'][0]);
        $url = preg_replace('#/(s\d+/)?([^/]+)$#', '/s0/'.basename($this->file), $url);

        return $url;
    }

    /**
     * Create new album and return albumId was created.
     *
     * @param string $title
     * @param string $access
     * @param string $description
     *
     * @return string|false
     *
     * @throws \Exception
     */
    public function addAlbum($title, $access = 'public', $description = '')
    {
        $this->checkValidToken(__METHOD__);
        $endpoint = sprintf(self::USER_FEED_ENDPOINT, $this->username);

        $this->resetHttpClient()
            ->setHeaders($this->getGeneralHeaders())
            ->setSubmitNormal('application/atom+xml')
            ->setRawPost(sprintf("<entry xmlns='http://www.w3.org/2005/Atom' "
                    ."xmlns:media='http://search.yahoo.com/mrss/' "
                    ."xmlns:gphoto='http://schemas.google.com/photos/2007'>"
                ."<title type='text'>%s</title>"
                ."<summary type='text'>%s</summary>"
                .'<gphoto:access>%s</gphoto:access>'
                ."<category scheme='http://schemas.google.com/g/2005#kind' "
                    ."term='http://schemas.google.com/photos/2007#album'></category>"
                .'</entry>', $title, $description, $access))
        ->execute($endpoint);

        $this->checkHttpClientErrors(__METHOD__);

        return $this->getMatch('#<id>.+?albumid/(.+?)</id>#i', $this->client, 1, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        $this->throwException('%s: Currently, this plugin does not support transload image.', __METHOD__);
    }

    /**
     * Check valid token.
     *
     * @param string $method
     *
     * @return void
     *
     * @throws Exception if have no valid token.
     */
    private function checkValidToken($method)
    {
        if (!$token = $this->getToken()) {
            $this->throwException('%s: You must have a valid token to perform this action.', __METHOD__);
        }
        if ($this->isTokenExpired($token)) {
            $this->refreshToken($token);
        }
    }

    /**
     * Gets authorization header.
     *
     * @return string
     */
    public function getAuthorizationHeader()
    {
        $token = $this->getToken();

        return sprintf('%s %s', $token['token_type'], $token['access_token']);
    }

    /**
     * Gets general headers.
     *
     * @return array
     */
    private function getGeneralHeaders()
    {
        return array(
            'Authorization' => $this->getAuthorizationHeader(),
            'GData-Version' => '2',
            'MIME-version'  => '1.0',
        );
    }
}
