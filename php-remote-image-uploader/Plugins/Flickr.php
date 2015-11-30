<?php
/**
 * Get API there: https://www.flickr.com/services/apps/create/noncommercial/.
 *
 * Sometimes yahoo require captcha for login,
 * the plugin can't bypass then it trigger an error message.
 * You should run script get AUTH_TOKEN, AUTH_SECRET
 * then call setAccessToken($token, $secret) before.
 *
 * @update May 04, 2015
 */
class ChipVN_ImageUploader_Plugins_Flickr extends ChipVN_ImageUploader_Plugins_Abstract
{
    const REQUEST_TOKEN_ENPOINT = 'https://www.flickr.com/services/oauth/request_token';
    const AUTH_ENDPOINT = 'https://www.flickr.com/services/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = 'https://www.flickr.com/services/oauth/access_token';
    const API_ENDPOINT = 'https://www.flickr.com/services/rest';
    const UPLOAD_ENDPOINT = 'https://www.flickr.com/services/upload/';

    const REQUEST_OAUTH_TOKEN = 'request_oauth_token';
    const REQUEST_OAUTH_SECRET = 'request_oauth_secret';
    const ACCESS_OAUTH_TOKEN = 'access_oauth_token';
    const ACCESS_OAUTH_SECRET = 'access_oauth_secret';

    /**
     * API secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Access OAuth Token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Access OAuth Secret.
     *
     * @var string
     */
    protected $accessSecret;

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
     * Set access token.
     *
     * @param string $token
     *
     * @return void
     */
    public function setAccessToken($token, $secret)
    {
        $this->accessToken = $token;
        $this->accessSecret = $secret;
    }

    /**
     * Get OAuth Access Token.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        if (!$token = $this->accessToken) {
            $token = $this->getCache()->get(self::ACCESS_OAUTH_TOKEN);
        }

        return $token;
    }

    /**
     * Get OAuth Access Secret.
     *
     * @return string
     */
    public function getAccessSecret()
    {
        if (!$token = $this->accessSecret) {
            $token = $this->getCache()->get(self::ACCESS_OAUTH_SECRET);
        }

        return $token;
    }

    /**
     * Get auth token.
     * This method will direct user to flickr to authorize
     * after success, flickr will direct user back to App url.
     *
     * @param string $callback
     *
     * @return array
     */
    public function getOAuthToken($callback = 'http://ptcong.com')
    {
        $cache = $this->getCache();

        if (empty($_GET['oauth_token']) && empty($_GET['oauth_verifier'])) {
            // direct user to flickr
            return $this->requestToken($callback);
        }

        return $this->getOAuthAccessToken(
            $_GET['oauth_token'], $_GET['oauth_verifier'],
            $this->getCache()->get(self::REQUEST_OAUTH_SECRET)
        );
    }

    /**
     * Direct user to Flickr to get authorisation.
     *
     * @param string $callback
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
     * We use OAuth so don't need username, password
     * for authenication.
     *
     * @return bool.
     */
    protected function doLogin()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        $params = $this->getParameters(array(
            // have issue with "signature" if file name have
            // some special chars
            // 'title'          => basename($this->file),
            'description'    => self::POWERED_BY,
            'tags'           => self::POWERED_BY,
            'is_public'      => 1,
            'is_friend'      => '',
            'is_family'      => '',
            'content_type'   => 1, // 1: photo, 2:screenshot
            'safety_level'   => '',
            'hidden'         => '',
            'oauth_token'    => $this->getAccessToken(),
        ));

        list($url, $params) = $this->prepareOAuthRequestData(self::UPLOAD_ENDPOINT, 'POST', $params);
        $params['photo'] = '@'.$this->file;

        $this->resetHttpClient()
            ->setParameters($params)
            ->setSubmitMultipart()
        ->execute($url);

        if (!$photoId = $this->getMatch('#<photoid>([\d]+)</photoid>#i', $this->client)) {
            parse_str($this->client, $result);
            if (isset($result['oauth_problem'])) {
                $this->throwException('UPLOAD_PROBLEM: "%s"', $result['oauth_problem']);
            } else {
                $error = $this->getMatch('#code="(.+?)"#', $this->client);
                $msg = $this->getMatch('#msg="(.+?)"#', $this->client);
                $this->throwException('UPLOAD_PROBLEM: "%s" (%d)', $msg, $error);
            }
        }
        $result = $this->call('flickr.photos.getInfo', array(
            'photo_id' => $photoId,
        ));

        return $this->getPhotoUrl($result['photo']);
    }

    /**
     * Get photo url.
     * {@link https://www.flickr.com/services/api/misc.urls.html}.
     *
     * @param array $info
     *
     * @return string
     */
    protected function getPhotoUrl(array $photo)
    {
        return strtr(
            'http://farm{farm-id}.staticflickr.com/{server-id}/{id}_{o-secret}_o.{o-format}',
            array(
                '{farm-id}'   => $photo['farm'],
                '{server-id}' => $photo['server'],
                '{id}'        => $photo['id'],
                '{o-secret}'  => $photo['originalsecret'],
                '{o-format}'  => $photo['originalformat'],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        $this->throwException('%s: Currently, this plugin doesn\'t support transload image.', __METHOD__);
    }

    /**
     * Prepare oauth request data and return url, parameters.
     *
     * @param string $url_endpoint
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    protected function prepareOAuthRequestData($url_endpoint, $method = 'GET', $params = array(), $secretKey2 = null)
    {
        $baseString = $this->getBaseString($url_endpoint, $method, $params);
        $params = $this->pushSignature($params, $baseString, $secretKey2);
        if ($method == 'GET') {
            $url = $url_endpoint.'?'.http_build_query($params);
        } else {
            $url = $url_endpoint;
        }

        return array($url, $params);
    }

    /**
     * Get OAuth parameters.
     *
     * @param array $extraParameters
     *
     * @return array
     */
    protected function getParameters(array $params)
    {
        $params = $params + array(
            'oauth_nonce'            => uniqid(),
            'oauth_timestamp'        => time(),
            'oauth_consumer_key'     => $this->apiKey,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version'          => '1.0',
        );
        ksort($params);

        return $params;
    }

    /**
     * Get OAuth base string.
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function getBaseString($url, $method, array $params)
    {
        return $method.'&'.urlencode($url).'&'.urlencode(http_build_query($params));
    }

    /**
     * Push OAuth signature.
     *
     * @param array  $params
     * @param string $baseString
     * @param string $secretKey2
     *
     * @return void
     */
    protected function pushSignature(&$params, $baseString, $secretKey2 = null)
    {
        $cache = $this->getCache();
        if ($secretKey2 === null && !$secretKey2 = $this->getAccessSecret()) {
            $secretKey2 = $cache->get(self::REQUEST_OAUTH_SECRET, '');
        }

        $secret = $this->secret.'&'.$secretKey2;
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $secret, true));

        return $params;
    }

    /**
     * Get REQUEST_AUTH_TOKEN url.
     *
     * @param string $callback
     *
     * @return string
     */
    protected function getRequestTokenUrl($callback)
    {
        $params = $this->getParameters(array(
            'oauth_callback' => $callback,
        ));
        list($url, $params) = $this->prepareOAuthRequestData(self::REQUEST_TOKEN_ENPOINT, 'GET', $params, '');

        $this->resetHttpClient();
        $this->client->execute($url);

        parse_str($this->client, $result);
        if (isset($result['oauth_problem'])) {
            $this->throwException('REQUEST_TOKEN_PROBLEM: "%s"', $result['oauth_problem']);
        }
        // oauth_callback_confirmed
        // oauth_token
        // oauth_token_secret
        $this->getCache()->set(self::REQUEST_OAUTH_TOKEN,   $result['oauth_token']);
        $this->getCache()->set(self::REQUEST_OAUTH_SECRET,  $result['oauth_token_secret']);

        list($url, ) = $this->prepareOAuthRequestData(self::AUTH_ENDPOINT, 'GET', array(
            'oauth_token' => $result['oauth_token'],
            'perms'       => 'write',
        ));

        return $url;
    }

    /**
     * Call API to get access token by request auth token, auth verifier.
     *
     * @param string      $requestAuthToken
     * @param string      $requestAuthVerifier
     * @param null|string $secretKey2          Use REQUEST_OAUTH_SECRET if run in public to get ACCESS_TOKEN
     *
     * @return array
     */
    protected function getOAuthAccessToken($requestAuthToken, $requestAuthVerifier, $secretKey2 = null)
    {
        // get access token
        $params = $this->getParameters(array(
            'oauth_token'    => $requestAuthToken,
            'oauth_verifier' => $requestAuthVerifier,
        ));
        list($url, $params) = $this->prepareOAuthRequestData(self::ACCESS_TOKEN_ENDPOINT, 'GET', $params, $secretKey2);

        $this->resetHttpClient()->execute($url);

        parse_str($this->client, $result);
        if (isset($result['oauth_problem'])) {
            $this->throwException('ACCESS_TOKEN_PROBLEM: "%s"', $result['oauth_problem']);
        }
        // fullname
        // oauth_token
        // oauth_token_secret
        // user_nsid
        // username
        $this->getCache()->set(self::ACCESS_OAUTH_TOKEN,   $result['oauth_token'],         3000);
        $this->getCache()->set(self::ACCESS_OAUTH_SECRET,  $result['oauth_token_secret'],  3000);

        return $result;
    }

    /**
     * Call Flickr OAuth API.
     *
     * @param string $method
     * @param array  $params
     *
     * @return array
     *
     * @throws Exception
     */
    protected function call($method, array $params = array())
    {
        $params += $this->getParameters($params + array(
            'method'         => $method,
            'oauth_token'    => $this->getAccessToken(),
            'format'         => 'json',
            'nojsoncallback' => '1',
        ));
        list($url, $params) = $this->prepareOAuthRequestData(self::API_ENDPOINT, 'GET', $params);

        $this->resetHttpClient()->execute($url);

        if (false === $result = json_decode($this->client, true)) {
            parse_str($this->client, $result);
            if (isset($result['oauth_problem'])) {
                $this->throwException('API_PROBLEM: "%s"', $result['oauth_problem']);
            }
        }
        if ($result['stat'] == 'fail') {
            $this->throwException('API_PROBLEM: "%s" fail. MESSAGE: "%s", CODE: "%s"',
                $method, $result['message'], $result['code']
            );
        }

        return $result;
    }
}
