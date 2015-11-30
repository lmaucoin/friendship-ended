<?php

abstract class ChipVN_ImageUploader_Plugins_Abstract
{
    const POWERED_BY = 'by-[ChipVN]-Image-Uploader';

    /**
     * Determine if login have called
     *
     * @since 5.2.0 - Jun 19, 2014
     * @var boolean
     */
    protected $useAccount = false;

    /**
     * Username to login hosting service.
     *
     * @var string
     */
    protected $username;

    /**
     * Password to login hosting service.
     *
     * @var string
     */
    protected $password;

    /**
     * API key if needed.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Image file path for uploading.
     *
     * @var string
     */
    protected $file;

    /**
     * Image url for transloading.
     *
     * @var string
     */
    protected $url;

    /**
     * Determine the plugin useCurl to send request
     *
     * @var boolean
     */
    protected $useCurl = false;

    /**
     * ChipVN_Http_Client instance for sending request.
     *
     * @var ChipVN_Http_Client
     */
    protected $client;

    /**
     * ChipVN_ImageUploader_Cache instrance for storage.
     *
     * @var ChipVN_ImageUploader_Cache
     */
    protected $cache;

    /**
     * Create a plugin instance.
     *
     * @return void
     */
    final public function __construct()
    {
        $this->client = $this->createHttpClient();
    }

    /**
     * Default use PHP native session.
     *
     * @return ChipVN_ImageUploader_Cache
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = ChipVN_Cache_Manager::make('Session');
        }
        // ensure don't overrides session of other accounts
        $this->cache->setOption('prefix', $this->getIdentifier());

        return $this->cache;
    }

    /**
     * Set cache instance.
     *
     * @param  string|ChipVN_Cache_Adapter_Interface $cache
     * @return ChipVN_Cache_Adapter_Interface
     */
    public function setCache($cache = '', array $options = array())
    {
        if ($cache instanceof ChipVN_Cache_Adapter_Abstract) {
            $this->cache = $cache;
        } elseif (is_string($cache)) {
            $this->cache = ChipVN_Cache_Manager::make($cache, $options + array(
                'prefix' => $this->getIdentifier(),
            ));
        }

        return $this->cache;
    }

    /**
     * Create new ChipVN_Http_Client instance
     *
     * @return ChipVN_Http_Client
     */
    public function createHttpClient()
    {
        $httpClient = new ChipVN_Http_Client();
        $httpClient->useCurl($this->useCurl);

        return $httpClient;
    }

    /**
     * Use cURL for sending request.
     *
     * @param  boolean $useCurl
     * @return void
     */
    public function useCurl($useCurl)
    {
        $this->useCurl = $useCurl;
    }

    /**
     * Reset request.
     *
     * @return ChipVN_Http_Client
     */
    protected function resetHttpClient()
    {
        $this->client->reset();
        $this->client->useCurl($this->useCurl);

        return $this->client;
    }

    /**
     * Execute login action and return results.
     *
     * @param  string  $username
     * @param  string  $password
     * @return boolean
     *
     * @throws Exception If login failed.
     */
    final public function login($username, $password)
    {
        $this->username   = $username;
        $this->password   = $password;
        $this->useAccount = true;

        return $this->doLogin($username, $password);
    }

    /**
     * Set API key.
     * You may set $apiKey by array of keys but it use only one for a request.
     *
     * @param  array|string $keys
     * @return void
     */
    final public function setApi($keys)
    {
        $keys = (array) $keys;

        $this->apiKey = $keys[array_rand($keys, 1)];
    }

    /**
     * Execute upload action and return URL.
     *
     * @param  string $file
     * @return string
     *
     * @throws Exception If have an error occurred
     */
    final public function upload($file)
    {
        if (!$filepath = realpath($file)) {
            $this->throwException('%s: File "%s" is not exists.', __METHOD__, $file);
        }
        if (!getimagesize($filepath)) {
            $this->throwException('%: The file "%s" is not an image.', __METHOD__, $file);
        }
        $this->file = $filepath;

        return $this->doUpload();
    }

    /**
     * Execute transload action and return URL.
     *
     * @param  string $url
     * @return string
     *
     * @throws Exception If have an error occurred
     */
    final public function transload($url)
    {
        $this->url = trim($url);

        return $this->doTransload();
    }

    /**
     * Get plugin identifier (hashed by name and username).
     *
     * @return string
     */
    final public function getIdentifier()
    {
        return substr(md5($this->getName().$this->username.$this->password), 0, 5);
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    final public function getName()
    {
        return get_class($this);
    }

    /**
     * Execute login action and return results.
     *
     * @return boolean
     *
     * @throws Exception If login failed.
     */
    abstract protected function doLogin();

    /**
     * Execute upload action and return URL.
     *
     * @return string|false
     *
     * @throws Exception If have an error occurred
     */
    abstract protected function doUpload();

    /**
     * Execute transload action and return URL.
     *
     * @return string|false
     *
     * @throws Exception If have an error occurred
     */
    abstract protected function doTransload();

    /**
     * Helper method to get an element from matched.
     *
     * @param  string  $regex
     * @param  string  $text
     * @param  integer $number
     * @param  mixed   $default
     * @return mixed
     */
    protected function getMatch($regex, $text, $number = 1, $default = null)
    {
        if (preg_match($regex, $text, $match) && isset($match[$number])) {
            return $match[$number];
        }

        return $default;
    }

    /**
     * Helper method to get element in an array
     *
     * @param  array $array
     * @param  key   $keys  index.index.index
     * @return mixed
     */
    protected function getElement($array, $keys, $default = null)
    {
        foreach (explode('.', $keys) as $key) {
            if (isset($array[$key])) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Throws an exception.
     *
     * @return Exception
     */
    protected function throwException()
    {
        $arguments = $this->trimBaseClassName(func_get_args());

        throw new Exception(call_user_func_array('sprintf', array_slice($arguments, 0, 10)));
    }

    /**
     * Check and throw http error if have.
     *
     * @param  string $method
     * @return void
     */
    protected function checkHttpClientErrors($method)
    {
        if ($this->client->errors) {
            $this->throwRequestError($method);
        }
    }

    /**
     * Throws an exception.
     *
     * @param  string    $method
     * @return Exception
     */
    protected function throwRequestError($method)
    {
        $method = $this->trimBaseClassName($method);

        return $this->throwException('%s: %s', $method, implode(', ', $this->client->errors));
    }

    /**
     * Trim base class name.
     *
     * @param  string $value
     * @return strin
     */
    protected function trimBaseClassName($value)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->trimBaseClassName($val);
            }
        } else {
            $value = strtr($value, array('ChipVN_ImageUploader_Plugins_' => ''));
        }

        return $value;
    }
}
