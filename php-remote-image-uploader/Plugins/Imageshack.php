<?php
/**
 * Use "imageshack.com" rest API v2, insteadof "imageshack.us".
 * Register an API here: {@link https://imageshack.com/contact/api}.
 * You must login and have an API for uploading, transloading.
 *
 * @update May 04, 2015
 */
class ChipVN_ImageUploader_Plugins_Imageshack extends ChipVN_ImageUploader_Plugins_Abstract
{
    const LOGIN_ENDPOINT = 'https://imageshack.com/rest_api/v2/user/login';
    const UPLOAD_ENPOINT = 'https://imageshack.com/rest_api/v2/images';

    const SESSION_LOGIN  = 'session_login';

    /**
     * Get API endpoint URL.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getApiURL($path)
    {
        return self::API_ENDPOINT.$path;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        // session_login is array
        if (!$this->getCache()->has(self::SESSION_LOGIN)) {
            $this->resetHttpClient()
                ->setReferer('https://imageshack.com/')
                ->setParameters(array(
                    'username'    => $this->username,
                    'password'    => $this->password,
                    'remember_me' => 'true',
                    'set_cookies' => 'true',
                ))
            ->execute(self::LOGIN_ENDPOINT, 'POST');

            $this->checkHttpClientErrors(__METHOD__);
            $result = json_decode($this->client, true);

            if (!empty($result['result']['userid'])) {
                $this->getCache()->set(self::SESSION_LOGIN, $result['result']);
            } else {
                if (isset($result['error']['error_message'])) {
                    $message = $result['error']['error_message'];
                } else {
                    $message = 'Login failed.';
                }
                $this->getCache()->delete(self::SESSION_LOGIN);

                $this->throwException('%s: %s.', __METHOD__, $message); // $this->client
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        return $this->sendRequest(array('file' => '@'.$this->file));
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        return $this->sendRequest(array('url' => $this->url));
    }

    /**
     * Send request to API and get image URL.
     *
     * @param array $param
     *
     * @return string
     *
     * @throws \Exception If have an error occured
     */
    private function sendRequest(array $param)
    {
        if (!$this->getCache()->has(self::SESSION_LOGIN) || empty($this->apiKey)) {
            $this->throwException(
                'You must be loggedin and have an API key. Register API here: https://imageshack.com/contact/api'
            );
        }

        $session = $this->getCache()->get(self::SESSION_LOGIN);

        $this->resetHttpClient()
            ->setSubmitMultipart()
            ->setParameters($param + array(
                'auth_token' => $session['auth_token'],
                'api_key'    => $this->apiKey,
            ))
            ->execute(self::UPLOAD_ENPOINT, 'POST');

        $this->checkHttpClientErrors(__METHOD__);
        $result = json_decode($this->client, true);


        if (isset($result['error']['error_message'])) {
            $this->throwException(__METHOD__.': '.$result['error']['error_message']);
        } elseif (isset($result['result']['images'][0])) {
            return 'http://'.$result['result']['images'][0]['direct_link'];
        }

        return false;
    }
}
