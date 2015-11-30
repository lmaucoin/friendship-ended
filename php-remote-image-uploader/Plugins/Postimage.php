<?php
/**
 * Plugin for http://postimage.org.
 *
 * @release Jun 19, 2014
 * @update Jun 13, 2015
 */
class ChipVN_ImageUploader_Plugins_Postimage extends ChipVN_ImageUploader_Plugins_Abstract
{
    const UPLOAD_MAX_FILE_SIZE = 16777216;
    const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:38.0) Gecko/20100101 Firefox/38.0';
    const FREE_UPLOAD_ENPOINT = 'http://postimage.org/';
    const ACCOUNT_UPLOAD_ENPOINT = 'http://postimg.org/';

    const SESSION_LOGIN = 'session_login';

    /**
     * Gets upload url endpoint.
     *
     * @return string
     */
    private function getUrlEnpoint()
    {
        return $this->useAccount
            ? self::ACCOUNT_UPLOAD_ENPOINT
            : self::FREE_UPLOAD_ENPOINT;
    }

    /**
     * Gets cookies if have.
     *
     * @return array
     */
    private function getCookies()
    {
        return $this->getCache()->get(self::SESSION_LOGIN, array());
    }

    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        if (!$this->getCache()->has(self::SESSION_LOGIN)) {
            $this->resetHttpClient()
                ->setUserAgent(self::USER_AGENT)
                ->setFollowRedirect(true, 2)
                ->setParameters(array(
                    'login'    => $this->username,
                    'password' => $this->password,
                ))
            ->execute('http://postimage.org/profile.php', 'POST');

            $this->checkHttpClientErrors(__METHOD__);
            if (($c = $this->client->getResponseArrayCookies('userlogin')) && $c['value'] != 'deleted') {
                $this->getCache()->set(self::SESSION_LOGIN, $this->client->getResponseArrayCookies());
            } else {
                $this->getCache()->delete(self::SESSION_LOGIN);
                $this->throwException('%s: Login failed.', __METHOD__);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        $cookies = $this->getCookies();
        $endpoint = $this->getUrlEnpoint();
        $params = array(
            'upload'         => '@'.$this->file,
            'um'             => 'computer',
            'gallery_id'     => '',
            'upload_error'   => '',
            'session_upload' => time() * 1000 + mt_rand(0, 1000),
            'MAX_FILE_SIZE'  => self::UPLOAD_MAX_FILE_SIZE,
        );

        if ($this->useAccount) {
            $this->resetHttpClient()
                ->setUserAgent(self::USER_AGENT)
                ->setCookies($cookies)
            ->execute($endpoint);
            if (preg_match('#<select.*?name="gallery".*?<option value=[\'"]([^\'"]+)[\'"]#is', $this->client, $match)) {
                $params += array('gallery' => $match[1]);
            }
        }

        $this->resetHttpClient()
            ->setUserAgent(self::USER_AGENT)
            ->setCookies($cookies)
            ->setReferer($endpoint)
            ->setSubmitMultipart()
            ->setParameters($this->getUploadGeneralParams())
            ->setParameters($params)
        ->execute($endpoint);

        if (!$galleryId = $this->client->getResponseText()) {
            $this->throwException('%s: Not found galery id.', __METHOD__);
        }

        unset($params['upload']);
        $params = array(
            'upload[]'     => '',
            'gallery_id'   => $galleryId,
        ) + $params;

        $this->resetHttpClient()
            ->setUserAgent(self::USER_AGENT)
            ->setCookies($this->getCache()->get(self::SESSION_LOGIN))
            ->setFollowRedirect(true, 2) // upload -> gallery -> image
            ->setSubmitMultipart()
            ->setReferer($endpoint)
            ->setParameters($this->getUploadGeneralParams())
            ->setParameters($params)
        ->execute($endpoint);

        $this->checkHttpClientErrors(__METHOD__);

        return $this->getImageFromResult($this->client);
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        $endpoint = $this->getUrlEnpoint();
        $cookies = $this->getCookies();
        $params = array(
            'um'       => 'web',
            'url_list' => $this->url,
        );

        if ($this->useAccount) {
            $this->resetHttpClient()
                ->setUserAgent(self::USER_AGENT)
                ->setCookies($cookies)
            ->execute($endpoint);
            if (preg_match('#<select.*?name="gallery".*?<option value=[\'"]([^\'"]+)[\'"]#is', $this->client, $match)) {
                $params += array('gallery' => $match[1]);
            }
        }
        $this->resetHttpClient()
            ->setUserAgent(self::USER_AGENT)
            ->setCookies($this->getCache()->get(self::SESSION_LOGIN))
            ->setFollowRedirect(true, 1) // transload -> image
            ->setReferer($endpoint)
            ->setParameters($this->getUploadGeneralParams())
            ->setParameters(array(
                'um'       => 'web',
                'url_list' => $this->url,
            ))
        ->execute($endpoint, 'POST');

        $this->checkHttpClientErrors(__METHOD__);

        return $this->getImageFromResult($this->client);
    }

    /**
     * General parameters for sending.
     *
     * @return array
     */
    private function getUploadGeneralParams()
    {
        $endpoint = $this->getUrlEnpoint();
        $ui = sprintf('24__1440__900__true__?__?__%s__%s__', date('m/d/Y, h:i:s A'), self::USER_AGENT);

        return array(
            'mode'           => 'local',
            'areaid'         => '',
            'hash'           => '',
            'code'           => '',
            'content'        => '',
            'tpl'            => '.',
            'ver'            => '',
            'addform'        => '',
            'mforum'         => '',
            'forumurl'       => $endpoint,
            'adult'          => 'no',
            'optsize'        => 0,
            'ui'             => $ui
        );
    }

    /**
     * Gets image url from result.
     *
     * @return string image url
     *
     * @throws Exception if not found direct link
     */
    private function getImageFromResult($client)
    {
        if (!stripos($client, 'Direct Link')) {
            $this->throwException('%s: Can\'t get image direct link.', __METHOD__);
        }
        $imageUrl = $this->getMatch('#id="code_2"[^>]*?>(http[^<]+)#', $this->client);

        return $imageUrl;
    }
}
