<?php
/**
 * You must loggedin for uploading.
 * This plugin doesn't support transloading.
 *
 * @lastupdate Jan 20, 2015
 */

class ChipVN_ImageUploader_Plugins_Picasa extends ChipVN_ImageUploader_Plugins_Abstract
{
    const CLIENT_LOGIN_ENDPOINT = 'https://www.google.com/accounts/ClientLogin';
    const API_ENDPOINT          = 'https://picasaweb.google.com/data/feed/api';
    const PATH_USER             = 'user';
    const PATH_ALBUMID          = 'albumid';

    const SESSION_LOGIN         = 'session_login';

    /*
     * AlbumId to archive image.
     * Account upload limits:
     *
     * Maximum photo size: Each image can be no larger than 20 megabytes and are restricted to 50 megapixels or less.
     * Maximum video size: Each video uploaded can be no larger than 1GB in size.
     * Maximum number of web albums: 20,000
     * Maximum number of photos and videos per web album: 1,000
     * Total storage space: Picasa Web provides 1 GB for photos and videos. Files under
     *
     * @var string
     */
    private $albumId = 'default';

    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        // normalize username
        $this->username = preg_replace('#@gmail\.com#i', '', $this->username);

        if (!$this->getCache()->has(self::SESSION_LOGIN)) {
            $this->resetHttpClient()
                ->setParameters(array(
                    'accountType' => 'HOSTED_OR_GOOGLE',
                    'Email'       => $this->username,
                    'Passwd'      => $this->password,
                    'source'      => self::POWERED_BY,
                    'service'     => 'lh2',
                ))
            ->execute(self::CLIENT_LOGIN_ENDPOINT, 'POST');

            $this->checkHttpClientErrors(__METHOD__);

            if ($cookie = $this->getMatch('#Auth=([a-z0-9_\-]+)#i', $this->client)) {
                $this->getCache()->set(self::SESSION_LOGIN, $cookie, 900);
            } elseif (
                ($error = $this->getMatch('#Error=(.+)#i', $this->client))
                && ($info = $this->getMatch('#Info=(.+)#i', $this->client))
            ) {
                $this->getCache()->delete(self::SESSION_LOGIN);

                $this->throwException('%s: Error=%s. Info=%s', __METHOD__, $error, $info);
            } else {
                $this->getCache()->delete(self::SESSION_LOGIN);

                $this->throwException('%s: Login failed.', __METHOD__);
            }
        }

        return true;
    }

    /**
     * Set AlbumID.
     * You can set AlbumId by an array, then method will get random an id
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
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        $this->checkPermission(__METHOD__);

        $this->resetHttpClient()
            ->setHeaders($this->getGeneralHeaders())
            ->setSubmitNormal('image/jpeg')
            ->setHeaders(array(
                'Slug: '.basename($this->file),
            ))
            ->setRawPostFile($this->file)
            ->setTarget($this->getAlbumEndpoint($this->albumId).'?alt=json')
        ->execute();

        $this->checkHttpClientErrors(__METHOD__);
        $result = json_decode($this->client, true);

        if ($this->client->getResponseStatus() != 201 || empty($result['entry']['media$group']['media$content'][0])) {
            $this->throwException('%s: Upload failed. %s', __METHOD__, $this->client);
        }

        // url, width, height, type
        extract($result['entry']['media$group']['media$content'][0]);
        $url = preg_replace('#/(s\d+/)?([^/]+)$#', '/s0/'.basename($this->file), $url);

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        $this->throwException('%s: Currently, this plugin does not support transload image.', __METHOD__);
    }

    /**
     * Create new album and return albumId was created.
     *
     * @param  string       $title
     * @param  string       $access
     * @param  string       $description
     * @return string|false
     *
     * @throws \Exception
     */
    public function addAlbum($title, $access = 'public', $description = '')
    {
        $this->checkPermission(__METHOD__);

        $this->resetHttpClient()
            ->setHeaders($this->getGeneralHeaders())
            ->setSubmitNormal("application/atom+xml")
            ->setRawPost(sprintf("<entry xmlns='http://www.w3.org/2005/Atom' xmlns:media='http://search.yahoo.com/mrss/' xmlns:gphoto='http://schemas.google.com/photos/2007'>
            <title type='text'>%s</title>
            <summary type='text'>%s</summary>
            <gphoto:access>%s</gphoto:access>
            <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/photos/2007#album'></category>
            </entry>", $title, $description, $access))
        ->execute($this->getUserEndpoint());

        $this->checkHttpClientErrors(__METHOD__);

        return $this->getMatch('#<id>.+?albumid/(.+?)</id>#i', $this->client, 1, false);
    }

    /**
     * Get user api endpoint.
     *
     * @return string
     */
    private function getUserEndpoint()
    {
        return self::API_ENDPOINT.'/'.self::PATH_USER.'/'.$this->username;
    }

    /**
     * Get album api endpoint.
     *
     * @param  string $albumId
     * @return string
     */
    private function getAlbumEndpoint($albumId)
    {
        return $this->getUserEndpoint().'/'.self::PATH_ALBUMID.'/'.$albumId;
    }

    /**
     * @param  string     $method
     * @throws \Exception if session_login is empty
     */
    private function checkPermission($method)
    {
        if (!$this->getCache()->has(self::SESSION_LOGIN)) {
            $this->throwException('You must be logged in before call the method "%s"', $method);
        }
    }

    /**
     * Gets general headers.
     *
     * @return array
     */
    private function getGeneralHeaders()
    {
        return array(
            "Authorization: GoogleLogin auth=".$this->getCache()->get(self::SESSION_LOGIN),
            "MIME-Version: 1.0",
        );
    }
}
